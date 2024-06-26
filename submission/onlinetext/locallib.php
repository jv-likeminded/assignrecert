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
 * This file contains the definition for the library class for onlinetext submission plugin
 *
 * This class provides all the functionality for the new assignrecert module.
 *
 * @package assignrecertsubmission_onlinetext
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// File area for online text submission assignmentrecert.
define('assignrecertsubMISSION_ONLINETEXT_FILEAREA', 'submissions_onlinetext');

/**
 * library class for onlinetext submission plugin extending submission plugin base class
 *
 * @package assignrecertsubmission_onlinetext
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignrecert_submission_onlinetext extends assignrecert_submission_plugin {

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('onlinetext', 'assignrecertsubmission_onlinetext');
    }


    /**
     * Get onlinetext submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_onlinetext_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignrecertsubmission_onlinetext', array('submission'=>$submissionid));
    }

    /**
     * Get the settings for onlinetext submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $defaultwordlimit = $this->get_config('wordlimit') == 0 ? '' : $this->get_config('wordlimit');
        $defaultwordlimitenabled = $this->get_config('wordlimitenabled');

        $options = array('size' => '6', 'maxlength' => '6');
        $name = get_string('wordlimit', 'assignrecertsubmission_onlinetext');

        // Create a text box that can be enabled/disabled for onlinetext word limit.
        $wordlimitgrp = array();
        $wordlimitgrp[] = $mform->createElement('text', 'assignrecertsubmission_onlinetext_wordlimit', get_string('wordlimit_number', 'assignrecertsubmission_onlinetext'), $options);
        $wordlimitgrp[] = $mform->createElement('checkbox', 'assignrecertsubmission_onlinetext_wordlimit_enabled',
                '', get_string('enable'), ['aria-label' => get_string('wordlimit_enable', 'assignrecertsubmission_onlinetext')]);
        $mform->addGroup($wordlimitgrp, 'assignrecertsubmission_onlinetext_wordlimit_group', $name, ' ', false);
        $mform->addHelpButton('assignrecertsubmission_onlinetext_wordlimit_group',
                              'wordlimit',
                              'assignrecertsubmission_onlinetext');
        $mform->disabledIf('assignrecertsubmission_onlinetext_wordlimit',
                           'assignrecertsubmission_onlinetext_wordlimit_enabled',
                           'notchecked');

        // Add numeric rule to text field.
        $wordlimitgrprules = array();
        $wordlimitgrprules['assignrecertsubmission_onlinetext_wordlimit'][] = array(null, 'numeric', null, 'client');
        $mform->addGroupRule('assignrecertsubmission_onlinetext_wordlimit_group', $wordlimitgrprules);

        // Rest of group setup.
        $mform->setDefault('assignrecertsubmission_onlinetext_wordlimit', $defaultwordlimit);
        $mform->setDefault('assignrecertsubmission_onlinetext_wordlimit_enabled', $defaultwordlimitenabled);
        $mform->setType('assignrecertsubmission_onlinetext_wordlimit', PARAM_INT);
        $mform->disabledIf('assignrecertsubmission_onlinetext_wordlimit_group',
                           'assignrecertsubmission_onlinetext_enabled',
                           'notchecked');
    }

    /**
     * Save the settings for onlinetext submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        if (empty($data->assignrecertsubmission_onlinetext_wordlimit) || empty($data->assignrecertsubmission_onlinetext_wordlimit_enabled)) {
            $wordlimit = 0;
            $wordlimitenabled = 0;
        } else {
            $wordlimit = $data->assignrecertsubmission_onlinetext_wordlimit;
            $wordlimitenabled = 1;
        }

        $this->set_config('wordlimit', $wordlimit);
        $this->set_config('wordlimitenabled', $wordlimitenabled);

        return true;
    }

    /**
     * Add form elements for settings
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        $elements = array();

        $editoroptions = $this->get_edit_options();
        $submissionid = $submission ? $submission->id : 0;

        if (!isset($data->onlinetext)) {
            $data->onlinetext = '';
        }
        if (!isset($data->onlinetextformat)) {
            $data->onlinetextformat = editors_get_preferred_format();
        }

        if ($submission) {
            $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
            if ($onlinetextsubmission) {
                $data->onlinetext = $onlinetextsubmission->onlinetext;
                $data->onlinetextformat = $onlinetextsubmission->onlineformat;
            }

        }

        $data = file_prepare_standard_editor($data,
                                             'onlinetext',
                                             $editoroptions,
                                             $this->assignmentrecert->get_context(),
                                             'assignrecertsubmission_onlinetext',
                                             assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                                             $submissionid);
        $mform->addElement('editor', 'onlinetext_editor', $this->get_name(), null, $editoroptions);

        return true;
    }

    /**
     * Editor format options
     *
     * @return array
     */
    private function get_edit_options() {
         $editoroptions = array(
           'noclean' => false,
           'maxfiles' => EDITOR_UNLIMITED_FILES,
           'maxbytes' => $this->assignmentrecert->get_course()->maxbytes,
           'context' => $this->assignmentrecert->get_context(),
           'return_types' => FILE_INTERNAL | FILE_EXTERNAL
        );
        return $editoroptions;
    }

    /**
     * Save data to the database and trigger plagiarism plugin,
     * if enabled, to scan the uploaded content via events trigger
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $editoroptions = $this->get_edit_options();

        $data = file_postupdate_standard_editor($data,
                                                'onlinetext',
                                                $editoroptions,
                                                $this->assignmentrecert->get_context(),
                                                'assignrecertsubmission_onlinetext',
                                                assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                                                $submission->id);

        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);

        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignmentrecert->get_context()->id,
                                     'assignrecertsubmission_onlinetext',
                                     assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                                     $submission->id,
                                     'id',
                                     false);

        // Check word count before submitting anything.
        $exceeded = $this->check_word_count($data->onlinetext, $data);
        if ($exceeded) {
            $this->set_error($exceeded);
            return false;
        }

        $params = array(
            'context' => context_module::instance($this->assignmentrecert->get_course_module()->id),
            'courseid' => $this->assignmentrecert->get_course()->id,
            'objectid' => $submission->id,
            'other' => array(
                'pathnamehashes' => array_keys($files),
                'content' => trim($data->onlinetext),
                'format' => $data->onlinetext_editor['format']
            )
        );
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
            $params['relateduserid'] = $submission->userid;
        }
        $event = \assignrecertsubmission_onlinetext\event\assessable_uploaded::create($params);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Totara: Look after Weka
        if (($count = $this->count_words_for_weka($data)) === null) {
            $count = count_words($data->onlinetext);
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'onlinetextwordcount' => $count,
            'groupid' => $groupid,
            'groupname' => $groupname
        );

        if ($onlinetextsubmission) {

            $onlinetextsubmission->onlinetext = $data->onlinetext;
            $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];
            $params['objectid'] = $onlinetextsubmission->id;
            $updatestatus = $DB->update_record('assignrecertsubmission_onlinetext', $onlinetextsubmission);
            $event = \assignrecertsubmission_onlinetext\event\submission_updated::create($params);
            $event->set_assignrecert($this->assignmentrecert);
            $event->trigger();
            return $updatestatus;
        } else {

            $onlinetextsubmission = new stdClass();
            $onlinetextsubmission->onlinetext = $data->onlinetext;
            $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];

            $onlinetextsubmission->submission = $submission->id;
            $onlinetextsubmission->assignmentrecert = $this->assignmentrecert->get_instance()->id;
            $onlinetextsubmission->id = $DB->insert_record('assignrecertsubmission_onlinetext', $onlinetextsubmission);
            $params['objectid'] = $onlinetextsubmission->id;
            $event = \assignrecertsubmission_onlinetext\event\submission_created::create($params);
            $event->set_assignrecert($this->assignmentrecert);
            $event->trigger();
            return $onlinetextsubmission->id > 0;
        }
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('onlinetext' => get_string('pluginname', 'assignrecertsubmission_onlinetext'));
    }

    /**
     * Get the saved text content from the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return string
     */
    public function get_editor_text($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetextsubmission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlinetext;
            }
        }

        return '';
    }

    /**
     * Get the content format for the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return int
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetextsubmission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlineformat;
            }
        }

        return 0;
    }


     /**
      * Display onlinetext word count in the submission status table
      *
      * @param stdClass $submission
      * @param bool $showviewlink - If the summary has been truncated set this to true
      * @return string
      */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $CFG;

        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        // Always show the view link.
        $showviewlink = true;

        if ($onlinetextsubmission) {
            // This contains the shortened version of the text plus an optional 'Export to portfolio' button.
            $text = $this->assignmentrecert->render_editor_content(assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                                                             $onlinetextsubmission->submission,
                                                             $this->get_type(),
                                                             'onlinetext',
                                                             'assignrecertsubmission_onlinetext', true);

            // The actual submission text.
            $onlinetext = trim($onlinetextsubmission->onlinetext);

            // Totara: Look after Weka.
            $plugin = $this->assignmentrecert->get_submission_plugin_by_type($this->get_type());
            $format = $plugin->get_editor_format('onlinetext', $onlinetextsubmission->submission);
            if ($format == FORMAT_JSON_EDITOR) {
                $onlinetext = file_rewrite_pluginfile_urls(
                    $onlinetext,
                    'pluginfile.php',
                    $this->assignmentrecert->get_context()->id,
                    'assignrecertsubmission_onlinetext',
                    assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                    $submission->id);
                $onlinetext = format_text($onlinetext, $format);
            }

            // The shortened version of the submission text.
            $shorttext = shorten_text($onlinetext, 140);

            $plagiarismlinks = '';

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $plagiarismlinks .= plagiarism_get_links(array('userid' => $submission->userid,
                    'content' => trim($onlinetextsubmission->onlinetext),
                    'cmid' => $this->assignmentrecert->get_course_module()->id,
                    'course' => $this->assignmentrecert->get_course()->id,
                    'assignmentrecert' => $submission->assignmentrecert));
            }
            // We compare the actual text submission and the shortened version. If they are not equal, we show the word count.
            if ($onlinetext != $shorttext) {
                $wordcount = get_string('numwords', 'assignrecertsubmission_onlinetext', count_words($onlinetext));

                return $plagiarismlinks . $wordcount . $text;
            } else {
                return $plagiarismlinks . $text;
            }
        }
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission.
     *
     * @param stdClass $submission - For this is the submission data
     * @param stdClass $user - This is the user record for this submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        global $DB;

        $files = array();
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);

        // Note that this check is the same logic as the result from the is_empty function but we do
        // not call it directly because we alread have the submission record.
        if ($onlinetextsubmission && !empty($onlinetextsubmission->onlinetext)) {
            $finaltext = $this->assignmentrecert->download_rewrite_pluginfile_urls($onlinetextsubmission->onlinetext, $user, $this);
            $formattedtext = format_text($finaltext,
                                         $onlinetextsubmission->onlineformat,
                                         array('context'=>$this->assignmentrecert->get_context()));
            $head = '<head><meta charset="UTF-8"></head>';
            $submissioncontent = '<!DOCTYPE html><html>' . $head . '<body>'. $formattedtext . '</body></html>';

            $filename = get_string('onlinetextfilename', 'assignrecertsubmission_onlinetext');
            $files[$filename] = array($submissioncontent);

            $fs = get_file_storage();

            $fsfiles = $fs->get_area_files($this->assignmentrecert->get_context()->id,
                                           'assignrecertsubmission_onlinetext',
                                           assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                                           $submission->id,
                                           'timemodified',
                                           false);

            foreach ($fsfiles as $file) {
                $files[$file->get_filename()] = $file;
            }
        }

        return $files;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $CFG;
        $result = '';

        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);

        if ($onlinetextsubmission) {

            // Render for portfolio API.
            $result .= $this->assignmentrecert->render_editor_content(assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                                                                $onlinetextsubmission->submission,
                                                                $this->get_type(),
                                                                'onlinetext',
                                                                'assignrecertsubmission_onlinetext');

            $plagiarismlinks = '';

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $plagiarismlinks .= plagiarism_get_links(array('userid' => $submission->userid,
                    'content' => trim($onlinetextsubmission->onlinetext),
                    'cmid' => $this->assignmentrecert->get_course_module()->id,
                    'course' => $this->assignmentrecert->get_course()->id,
                    'assignmentrecert' => $submission->assignmentrecert));
            }
        }

        return $plagiarismlinks . $result;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // Format the info for each submission plugin (will be logged).
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        $onlinetextloginfo = '';
        $onlinetextloginfo .= get_string('numwordsforlog',
                                         'assignrecertsubmission_onlinetext',
                                         // Totara: Look after Weka
                                         $this->count_words_for_weka($onlinetextsubmission) ?? count_words($onlinetextsubmission->onlinetext));

        return $onlinetextloginfo;
    }

    /**
     * The assignmentrecert has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records('assignrecertsubmission_onlinetext',
                            array('assignmentrecert'=>$this->assignmentrecert->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        $wordcount = 0;

        if (isset($onlinetextsubmission->onlinetext)) {
            // Totara: Look after Weka
            if (($wordcount = $this->count_words_for_weka($onlinetextsubmission)) === null) {
                $wordcount = count_words(trim($onlinetextsubmission->onlinetext));
            }

            if ($wordcount == 0 && isset($onlinetextsubmission->onlineformat)) {
                $wordcount = $this->count_words_for_image_tag($onlinetextsubmission->onlinetext);
            }
        }

        return $wordcount == 0;
    }

    /**
     * Determine if a submission is empty
     *
     * This is distinct from is_empty in that it is intended to be used to
     * determine if a submission made before saving is empty.
     *
     * @param stdClass $data The submission data
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        if (!isset($data->onlinetext_editor)) {
            return true;
        }
        $wordcount = 0;

        if (isset($data->onlinetext_editor['text'])) {
            // Totara: Look after Weka
            if (($wordcount = $this->count_words_for_weka($data)) === null) {
                $wordcount = count_words(trim((string)$data->onlinetext_editor['text']));
            }

            if ($wordcount == 0 && isset($data->onlinetext_editor['format'])) {
                $wordcount = $this->count_words_for_image_tag($data->onlinetext_editor['text']);
            }
        }

        return $wordcount == 0;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(assignrecertsubMISSION_ONLINETEXT_FILEAREA=>$this->get_name());
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy the files across (attached via the text editor).
        $contextid = $this->assignmentrecert->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'assignrecertsubmission_onlinetext',
                                     assignrecertsubMISSION_ONLINETEXT_FILEAREA, $sourcesubmission->id, 'id', false);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $destsubmission->id);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }

        // Copy the assignrecertsubmission_onlinetext record.
        $onlinetextsubmission = $this->get_onlinetext_submission($sourcesubmission->id);
        if ($onlinetextsubmission) {
            unset($onlinetextsubmission->id);
            $onlinetextsubmission->submission = $destsubmission->id;
            $DB->insert_record('assignrecertsubmission_onlinetext', $onlinetextsubmission);
        }
        return true;
    }

    /**
     * Return a description of external params suitable for uploading an onlinetext submission from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        $editorparams = array('text' => new external_value(PARAM_RAW, 'The text for this submission.'),
                              'format' => new external_value(PARAM_INT, 'The format for this submission'),
                              'itemid' => new external_value(PARAM_INT, 'The draft area id for files attached to the submission'));
        $editorstructure = new external_single_structure($editorparams, 'Editor structure', VALUE_OPTIONAL);
        return array('onlinetext_editor' => $editorstructure);
    }

    /**
     * Compare word count of onlinetext submission to word limit, and return result.
     *
     * @param string $submissiontext Onlinetext submission text from editor
     * @param null|stdClass $submission Totara: additional submission data object
     * @return string Error message if limit is enabled and exceeded, otherwise null
     */
    public function check_word_count($submissiontext, $submission = null) {
        global $OUTPUT;

        $wordlimitenabled = $this->get_config('wordlimitenabled');
        $wordlimit = $this->get_config('wordlimit');

        if ($wordlimitenabled == 0) {
            return null;
        }

        // Totara: Look after Weka.
        if ($submission === null || ($wordcount = $this->count_words_for_weka($submission)) === null) {
            // Count words and compare to limit.
            $wordcount = count_words($submissiontext);
        }

        if ($wordcount <= $wordlimit) {
            return null;
        } else {
            $errormsg = get_string('wordlimitexceeded', 'assignrecertsubmission_onlinetext',
                    array('limit' => $wordlimit, 'count' => $wordcount));
            return $OUTPUT->error_text($errormsg);
        }
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

    /**
     * Wrap around count_words to deal with a json content.
     *
     * @param object $data submission data object
     * @return integer|null pass through count_words() if null is returned
     * @since Totara 13.1
     */
    private function count_words_for_weka($data): ?int {
        $id = null;
        $onlinetext = null;
        $format = null;

        if (is_object($data)) {
            if (isset($data->onlinetext) && isset($data->onlinetextformat) && $data->onlinetextformat == FORMAT_JSON_EDITOR) {
                // data = {id, onlinetext, onlinetextformat}
                $id = $data->id;
                $onlinetext = $data->onlinetext;
                $format = $data->onlinetextformat;
            }
            if (isset($data->onlinetext) && isset($data->onlineformat) && $data->onlineformat == FORMAT_JSON_EDITOR) {
                // data = {id, onlinetext, onlineformat}
                $id = $data->id;
                $onlinetext = $data->onlinetext;
                $format = $data->onlineformat;
            }
            if (isset($data->onlinetext_editor) && is_array($data->onlinetext_editor)
                && isset($data->onlinetext_editor['text']) && isset($data->onlinetext_editor['format'])
                && $data->onlinetext_editor['format'] == FORMAT_JSON_EDITOR) {
                // data = {id, onlinetext_editor: [format, text]}
                $id = $data->id;
                $onlinetext = (string)$data->onlinetext_editor['text'];
                $format = $data->onlinetext_editor['format'];
            }
        }

        if ($id !== null && $onlinetext !== null && $format !== null) {
            $onlinetext = file_rewrite_pluginfile_urls(
                $onlinetext,
                'pluginfile.php',
                $this->assignmentrecert->get_context()->id,
                'assignrecertsubmission_onlinetext',
                assignrecertsubMISSION_ONLINETEXT_FILEAREA,
                $id);
            $onlinetext = format_text($onlinetext, $format);
            $count = count_words(trim($onlinetext));

            // Check online text is html image or not.
            if (core_text::strlen($onlinetext) > 0 && $count == 0) {
                $count = $this->count_words_for_image_tag($onlinetext);
            }

            return $count;
        }

        return null;
    }

    /**
     * Check data input is only image without any text.
     *
     * @param string $text
     * @return int
     */
    private function count_words_for_image_tag(string $text): int {
        if (isset($text)) {
            if (core_text::strlen($text) > 0) {
                $doc = new DOMDocument();
                $doc->loadHTML($text);

                // Check input data is image or not.
                if ($doc->getElementsByTagName('img')->count() > 0 ) {
                    return $doc->getElementsByTagName('img')->count();
                }
            }
        }

        return 0;
    }
}
