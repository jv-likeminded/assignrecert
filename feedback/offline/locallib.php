<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the library class for file feedback plugin
 *
 *
 * @package   assignrecertfeedback_offline
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/grading/lib.php');

/**
 * library class for file feedback plugin extending feedback plugin base class
 *
 * @package   assignrecertfeedback_offline
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignrecert_feedback_offline extends assignrecert_feedback_plugin {

    /** @var boolean|null $enabledcache Cached lookup of the is_enabled function */
    private $enabledcache = null;

    /**
     * Get the name of the file feedback plugin
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignrecertfeedback_offline');
    }

    /**
     * Get form elements for grading form
     *
     * @param stdClass $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements($grade, MoodleQuickForm $mform, stdClass $data) {
        return false;
    }

    /**
     * Return true if there are no feedback files
     * @param stdClass $grade
     */
    public function is_empty(stdClass $grade) {
        return true;
    }

    /**
     * This plugin does not save through the normal interface so this returns false.
     *
     * @param stdClass $grade The grade.
     * @param stdClass $data Form data from the feedback form.
     * @return boolean - False
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        return false;
    }

    /**
     * Loop through uploaded grades and update the grades for this assignmentrecert
     *
     * @param int $draftid - The unique draft item id for this import
     * @param int $importid - The unique import ID for this csv import operation
     * @param bool $ignoremodified - Ignore the last modified date when checking fields
     * @param string $encoding - Encoding of the file being processed.
     * @param string $separator - The character used to separate the information.
     * @return string - The html response
     */
    public function process_import_grades($draftid, $importid, $ignoremodified, $encoding = 'utf-8', $separator = 'comma') {
        global $USER, $DB;

        require_sesskey();
        require_capability('mod/assignrecert:grade', $this->assignmentrecert->get_context());

        $gradeimporter = new assignrecertfeedback_offline_grade_importer($importid, $this->assignmentrecert, $encoding, $separator);

        $context = context_user::instance($USER->id);
        $fs = get_file_storage();
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
            redirect(new moodle_url('view.php',
                                array('id'=>$this->assignmentrecert->get_course_module()->id,
                                      'action'=>'grading')));
            return;
        }
        $file = reset($files);

        $csvdata = $file->get_content();

        if ($csvdata) {
            $gradeimporter->parsecsv($csvdata);
        }
        if (!$gradeimporter->init()) {
            $thisurl = new moodle_url('/mod/assignrecert/view.php', array('action'=>'viewpluginpage',
                                                                     'pluginsubtype'=>'assignrecertfeedback',
                                                                     'plugin'=>'offline',
                                                                     'pluginaction'=>'uploadgrades',
                                                                     'id' => $this->assignmentrecert->get_course_module()->id));
            print_error('invalidgradeimport', 'assignrecertfeedback_offline', $thisurl);
            return;
        }
        // Does this assignmentrecert use a scale?
        $scaleoptions = null;
        if ($this->assignmentrecert->get_instance()->grade < 0) {
            if ($scale = $DB->get_record('scale', array('id'=>-($this->assignmentrecert->get_instance()->grade)))) {
                $scaleoptions = make_menu_from_list($scale->scale);
            }
        }
        // We may need to upgrade the gradebook comments after this update.
        $adminconfig = $this->assignmentrecert->get_admin_config();
        $gradebookplugin = $adminconfig->feedback_plugin_for_gradebook;

        $updatecount = 0;
        while ($record = $gradeimporter->next()) {
            $user = $record->user;
            $modified = $record->modified;
            $userdesc = fullname($user);
            $usergrade = $this->assignmentrecert->get_user_grade($user->id, false);

            if (!empty($scaleoptions)) {
                // This is a scale - we need to convert any grades to indexes in the scale.
                $scaleindex = array_search($record->grade, $scaleoptions);
                if ($scaleindex !== false) {
                    $record->grade = $scaleindex;
                } else {
                    $record->grade = '';
                }
            } else {
                $record->grade = unformat_float($record->grade);
            }

            // Note: Do not count the seconds when comparing modified dates.
            $skip = false;
            $stalemodificationdate = ($usergrade && $usergrade->timemodified > ($modified + 60));

            if ($usergrade && $usergrade->grade == $record->grade) {
                // Skip - grade not modified.
                $skip = true;
            } else if (!isset($record->grade) || $record->grade === '' || $record->grade < 0) {
                // Skip - grade has no value.
                $skip = true;
            } else if (!$ignoremodified && $stalemodificationdate) {
                // Skip - grade has been modified.
                $skip = true;
            } else if ($this->assignmentrecert->grading_disabled($record->user->id)) {
                // Skip grade is locked.
                $skip = true;
            } else if (($this->assignmentrecert->get_instance()->grade > -1) &&
                      (($record->grade < 0) || ($record->grade > $this->assignmentrecert->get_instance()->grade))) {
                // Out of range.
                $skip = true;
            }

            if (!$skip) {
                $grade = $this->assignmentrecert->get_user_grade($record->user->id, true);

                $grade->grade = $record->grade;
                $grade->grader = $USER->id;
                if ($this->assignmentrecert->update_grade($grade)) {
                    $this->assignmentrecert->notify_grade_modified($grade);
                    $updatecount += 1;
                }
            }

            if ($ignoremodified || !$stalemodificationdate) {
                foreach ($record->feedback as $feedback) {
                    $plugin = $feedback['plugin'];
                    $field = $feedback['field'];
                    $newvalue = $feedback['value'];
                    $description = $feedback['description'];
                    $oldvalue = '';
                    if ($usergrade) {
                        $oldvalue = $plugin->get_editor_text($field, $usergrade->id);
                        if (empty($oldvalue)) {
                            $oldvalue = '';
                        }
                    }
                    if ($newvalue != $oldvalue) {
                        $updatecount += 1;
                        $grade = $this->assignmentrecert->get_user_grade($record->user->id, true);
                        $this->assignmentrecert->notify_grade_modified($grade);
                        $plugin->set_editor_text($field, $newvalue, $grade->id);

                        // If this is the gradebook comments plugin - post an update to the gradebook.
                        if (($plugin->get_subtype() . '_' . $plugin->get_type()) == $gradebookplugin) {
                            $grade->feedbacktext = $plugin->text_for_gradebook($grade);
                            $grade->feedbackformat = $plugin->format_for_gradebook($grade);
                            $this->assignmentrecert->update_grade($grade);
                        }
                    }
                }
            }
        }
        $gradeimporter->close(true);

        $renderer = $this->assignmentrecert->get_renderer();
        $o = '';

        $o .= $renderer->render(new assignrecert_header($this->assignmentrecert->get_instance(),
                                                  $this->assignmentrecert->get_context(),
                                                  false,
                                                  $this->assignmentrecert->get_course_module()->id,
                                                  get_string('importgrades', 'assignrecertfeedback_offline')));
        $o .= $renderer->box(get_string('updatedgrades', 'assignrecertfeedback_offline', $updatecount));
        $url = new moodle_url('view.php',
                              array('id'=>$this->assignmentrecert->get_course_module()->id,
                                    'action'=>'grading'));
        $o .= $renderer->continue_button($url);
        $o .= $renderer->render_footer();
        return $o;
    }

    /**
     * Display upload grades form
     *
     * @return string The response html
     */
    public function upload_grades() {
        global $CFG, $USER;

        require_capability('mod/assignrecert:grade', $this->assignmentrecert->get_context());
        require_once($CFG->dirroot . '/mod/assignrecert/feedback/offline/uploadgradesform.php');
        require_once($CFG->dirroot . '/mod/assignrecert/feedback/offline/importgradesform.php');
        require_once($CFG->dirroot . '/mod/assignrecert/feedback/offline/importgradeslib.php');
        require_once($CFG->libdir . '/csvlib.class.php');

        $mform = new assignrecertfeedback_offline_upload_grades_form(null,
                                                              array('context'=>$this->assignmentrecert->get_context(),
                                                                    'cm'=>$this->assignmentrecert->get_course_module()->id));

        $o = '';

        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $renderer = $this->assignmentrecert->get_renderer();

        if ($mform->is_cancelled()) {
            redirect(new moodle_url('view.php',
                                    array('id'=>$this->assignmentrecert->get_course_module()->id,
                                          'action'=>'grading')));
            return;
        } else if (($data = $mform->get_data()) &&
                   ($csvdata = $mform->get_file_content('gradesfile'))) {

            $importid = csv_import_reader::get_new_iid('assignrecertfeedback_offline');
            $gradeimporter = new assignrecertfeedback_offline_grade_importer($importid, $this->assignmentrecert,
                    $data->encoding, $data->separator);
            // File exists and was valid.
            $ignoremodified = !empty($data->ignoremodified);

            $draftid = $data->gradesfile;

            // Preview import.

            $mform = new assignrecertfeedback_offline_import_grades_form(null, array('assignmentrecert'=>$this->assignmentrecert,
                                                                       'csvdata'=>$csvdata,
                                                                       'ignoremodified'=>$ignoremodified,
                                                                       'gradeimporter'=>$gradeimporter,
                                                                       'draftid'=>$draftid));

            $o .= $renderer->render(new assignrecert_header($this->assignmentrecert->get_instance(),
                                                            $this->assignmentrecert->get_context(),
                                                            false,
                                                            $this->assignmentrecert->get_course_module()->id,
                                                            get_string('confirmimport', 'assignrecertfeedback_offline')));
            $o .= $renderer->render(new assignrecert_form('confirmimport', $mform));
            $o .= $renderer->render_footer();
        } else if ($confirm) {
            $importid = optional_param('importid', 0, PARAM_INT);
            $draftid = optional_param('draftid', 0, PARAM_INT);
            $encoding = optional_param('encoding', 'utf-8', PARAM_ALPHANUMEXT); // Totara: encoding names include numbers!
            $separator = optional_param('separator', 'comma', PARAM_ALPHA);
            $ignoremodified = optional_param('ignoremodified', 0, PARAM_BOOL);
            $gradeimporter = new assignrecertfeedback_offline_grade_importer($importid, $this->assignmentrecert, $encoding, $separator);
            $mform = new assignrecertfeedback_offline_import_grades_form(null, array('assignmentrecert'=>$this->assignmentrecert,
                                                                       'csvdata'=>'',
                                                                       'ignoremodified'=>$ignoremodified,
                                                                       'gradeimporter'=>$gradeimporter,
                                                                       'draftid'=>$draftid));
            if ($mform->is_cancelled()) {
                redirect(new moodle_url('view.php',
                                        array('id'=>$this->assignmentrecert->get_course_module()->id,
                                              'action'=>'grading')));
                return;
            }

            $o .= $this->process_import_grades($draftid, $importid, $ignoremodified, $encoding, $separator);
        } else {

            $o .= $renderer->render(new assignrecert_header($this->assignmentrecert->get_instance(),
                                                            $this->assignmentrecert->get_context(),
                                                            false,
                                                            $this->assignmentrecert->get_course_module()->id,
                                                            get_string('uploadgrades', 'assignrecertfeedback_offline')));
            $o .= $renderer->render(new assignrecert_form('batchuploadfiles', $mform));
            $o .= $renderer->render_footer();
        }

        return $o;
    }

    /**
     * Download a marking worksheet
     *
     * @return string The response html
     */
    public function download_grades() {
        global $CFG;

        require_capability('mod/assignrecert:grade', $this->assignmentrecert->get_context());
        require_once($CFG->dirroot . '/mod/assignrecert/gradingtable.php');

        $groupmode = groups_get_activity_groupmode($this->assignmentrecert->get_course_module());
        // All users.
        $groupid = 0;
        $groupname = '';
        if ($groupmode) {
            $groupid = groups_get_activity_group($this->assignmentrecert->get_course_module(), true);
            $groupname = groups_get_group_name($groupid) . '-';
        }
        $filename = clean_filename(get_string('offlinegradingworksheet', 'assignrecertfeedback_offline') . '-' .
                                   $this->assignmentrecert->get_course()->shortname . '-' .
                                   $this->assignmentrecert->get_instance()->name . '-' .
                                   $groupname .
                                   $this->assignmentrecert->get_course_module()->id);

        $table = new assignrecert_grading_table($this->assignmentrecert, 0, '', 0, false, $filename);

        $table->out(0, false);
        return;
    }

    /**
     * Print a sub page in this plugin
     *
     * @param string $action - The plugin action
     * @return string The response html
     */
    public function view_page($action) {
        if ($action == 'downloadgrades') {
            return $this->download_grades();
        } else if ($action == 'uploadgrades') {
            return $this->upload_grades();
        }

        return '';
    }

    /**
     * Return a list of the grading actions performed by this plugin
     * This plugin supports upload zip
     *
     * @return array The list of grading actions
     */
    public function get_grading_actions() {
        return array('uploadgrades'=>get_string('uploadgrades', 'assignrecertfeedback_offline'),
                    'downloadgrades'=>get_string('downloadgrades', 'assignrecertfeedback_offline'));
    }

    /**
     * Override the default is_enabled to disable this plugin if advanced grading is active
     *
     * @return bool
     */
    public function is_enabled() {
        if ($this->enabledcache === null) {
            $gradingmanager = get_grading_manager($this->assignmentrecert->get_context(), 'mod_assignrecert', 'submissions');
            $controller = $gradingmanager->get_active_controller();
            $active = !empty($controller);

            if ($active) {
                $this->enabledcache = false;
            } else {
                $this->enabledcache = parent::is_enabled();
            }
        }
        return $this->enabledcache;
    }

    /**
     * Do not show this plugin in the grading table or on the front page
     *
     * @return bool
     */
    public function has_user_summary() {
        return false;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }
}
