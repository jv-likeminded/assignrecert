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
 * This file contains the forms to create and edit an instance of this module
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

/**
 * Assignment Recert settings form.
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assignrecert_mod_form extends moodleform_mod {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $CFG, $COURSE, $DB, $PAGE;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('assignmentname', 'assignrecert'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description', 'assignrecert'));

        $mform->addElement('filemanager', 'introattachments',
                            get_string('introattachments', 'assignrecert'),
                            null, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes) );
        $mform->addHelpButton('introattachments', 'introattachments', 'assignrecert');

        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('assignrecert', $this->current->id, 0, false, MUST_EXIST);
            $ctx = context_module::instance($cm->id);
        }
        $assignmentrecert = new assignrecert($ctx, null, null);
        if ($this->current && $this->current->course) {
            if (!$ctx) {
                $ctx = context_course::instance($this->current->course);
            }
            $course = $DB->get_record('course', array('id'=>$this->current->course), '*', MUST_EXIST);
            $assignmentrecert->set_course($course);
        }

        $config = get_config('assignrecert');

        $mform->addElement('header', 'availability', get_string('availability', 'assignrecert'));
        $mform->setExpanded('availability', true);

        $name = get_string('allowsubmissionsfromdate', 'assignrecert');
        $options = array('optional'=>true);
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', $name, $options);
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'assignrecert');

        $name = get_string('duedate', 'assignrecert');
        $mform->addElement('date_time_selector', 'duedate', $name, array('optional'=>true));
        $mform->addHelpButton('duedate', 'duedate', 'assignrecert');

        $name = get_string('cutoffdate', 'assignrecert');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional'=>true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'assignrecert');

        $name = get_string('alwaysshowdescription', 'assignrecert');
        $mform->addElement('advcheckbox', 'alwaysshowdescription', $name);
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'assignrecert');
        $mform->disabledIf('alwaysshowdescription', 'allowsubmissionsfromdate[enabled]', 'notchecked');

        $assignmentrecert->add_all_plugin_settings($mform);

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'assignrecert'));

        $name = get_string('submissiondrafts', 'assignrecert');
        $mform->addElement('selectyesno', 'submissiondrafts', $name);
        $mform->addHelpButton('submissiondrafts', 'submissiondrafts', 'assignrecert');

        $name = get_string('requiresubmissionstatement', 'assignrecert');
        $mform->addElement('selectyesno', 'requiresubmissionstatement', $name);
        $mform->addHelpButton('requiresubmissionstatement',
                              'requiresubmissionstatement',
                              'assignrecert');
        $mform->setType('requiresubmissionstatement', PARAM_BOOL);

        $options = array(
            ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_NONE => get_string('attemptreopenmethod_none', 'mod_assignrecert'),
            ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_MANUAL => get_string('attemptreopenmethod_manual', 'mod_assignrecert'),
            ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_UNTILPASS => get_string('attemptreopenmethod_untilpass', 'mod_assignrecert')
        );
        $mform->addElement('select', 'attemptreopenmethod', get_string('attemptreopenmethod', 'mod_assignrecert'), $options);
        $mform->addHelpButton('attemptreopenmethod', 'attemptreopenmethod', 'mod_assignrecert');

        $options = array(ASSIGNRECERT_UNLIMITED_ATTEMPTS => get_string('unlimitedattempts', 'mod_assignrecert'));
        $options += array_combine(range(1, 30), range(1, 30));
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'mod_assignrecert'), $options);
        $mform->addHelpButton('maxattempts', 'maxattempts', 'assignrecert');
        $mform->disabledIf('maxattempts', 'attemptreopenmethod', 'eq', ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_NONE);

        $mform->addElement('header', 'groupsubmissionsettings', get_string('groupsubmissionsettings', 'assignrecert'));

        $name = get_string('teamsubmission', 'assignrecert');
        $mform->addElement('selectyesno', 'teamsubmission', $name);
        $mform->addHelpButton('teamsubmission', 'teamsubmission', 'assignrecert');
        if ($assignmentrecert->has_submissions_or_grades()) {
            $mform->freeze('teamsubmission');
        }

        $name = get_string('preventsubmissionnotingroup', 'assignrecert');
        $mform->addElement('selectyesno', 'preventsubmissionnotingroup', $name);
        $mform->addHelpButton('preventsubmissionnotingroup',
            'preventsubmissionnotingroup',
            'assignrecert');
        $mform->setType('preventsubmissionnotingroup', PARAM_BOOL);
        $mform->hideIf('preventsubmissionnotingroup', 'teamsubmission', 'eq', 0);

        $name = get_string('requireallteammemberssubmit', 'assignrecert');
        $mform->addElement('selectyesno', 'requireallteammemberssubmit', $name);
        $mform->addHelpButton('requireallteammemberssubmit', 'requireallteammemberssubmit', 'assignrecert');
        $mform->hideIf('requireallteammemberssubmit', 'teamsubmission', 'eq', 0);
        $mform->disabledIf('requireallteammemberssubmit', 'submissiondrafts', 'eq', 0);

        $groupings = groups_get_all_groupings($assignmentrecert->get_course()->id);
        $options = array();
        $options[0] = get_string('none');
        foreach ($groupings as $grouping) {
            $options[$grouping->id] = $grouping->name;
        }

        $name = get_string('teamsubmissiongroupingid', 'assignrecert');
        $mform->addElement('select', 'teamsubmissiongroupingid', $name, $options);
        $mform->addHelpButton('teamsubmissiongroupingid', 'teamsubmissiongroupingid', 'assignrecert');
        $mform->hideIf('teamsubmissiongroupingid', 'teamsubmission', 'eq', 0);
        if ($assignmentrecert->has_submissions_or_grades()) {
            $mform->freeze('teamsubmissiongroupingid');
        }

        $mform->addElement('header', 'notifications', get_string('notifications', 'assignrecert'));

        $name = get_string('sendnotifications', 'assignrecert');
        $mform->addElement('selectyesno', 'sendnotifications', $name);
        $mform->addHelpButton('sendnotifications', 'sendnotifications', 'assignrecert');

        $name = get_string('sendlatenotifications', 'assignrecert');
        $mform->addElement('selectyesno', 'sendlatenotifications', $name);
        $mform->addHelpButton('sendlatenotifications', 'sendlatenotifications', 'assignrecert');
        $mform->disabledIf('sendlatenotifications', 'sendnotifications', 'eq', 1);

        $name = get_string('sendstudentnotificationsdefault', 'assignrecert');
        $mform->addElement('selectyesno', 'sendstudentnotifications', $name);
        $mform->addHelpButton('sendstudentnotifications', 'sendstudentnotificationsdefault', 'assignrecert');

        // Plagiarism enabling form.
        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir . '/plagiarismlib.php');
            plagiarism_get_form_elements_module($mform, $ctx->get_course_context(), 'mod_assignrecert');
        }

        $this->standard_grading_coursemodule_elements();
        $name = get_string('blindmarking', 'assignrecert');
        $mform->addElement('selectyesno', 'blindmarking', $name);
        $mform->addHelpButton('blindmarking', 'blindmarking', 'assignrecert');
        if ($assignmentrecert->has_submissions_or_grades() ) {
            $mform->freeze('blindmarking');
        }

        $name = get_string('markingworkflow', 'assignrecert');
        $mform->addElement('selectyesno', 'markingworkflow', $name);
        $mform->addHelpButton('markingworkflow', 'markingworkflow', 'assignrecert');

        $name = get_string('markingallocation', 'assignrecert');
        $mform->addElement('selectyesno', 'markingallocation', $name);
        $mform->addHelpButton('markingallocation', 'markingallocation', 'assignrecert');
        $mform->disabledIf('markingallocation', 'markingworkflow', 'eq', 0);

        $this->standard_coursemodule_elements();
        $this->apply_admin_defaults();

        $this->add_action_buttons();
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Totara: Check that grade to pass is set if it is required for completion.
        if (empty($data['gradepass']) || $data['gradepass'] <= 0) {
            if (!empty($data['completionunlocked'])) {
                if ($data['completion'] == COMPLETION_TRACKING_AUTOMATIC && !empty($data['completionpass'])) {
                    $errors['gradepass'] = get_string('gradepassrequiredforcompletion', 'mod_assignrecert');
                }
            } else if ($data['instance']) {
                $completionpass = $DB->get_field('assignrecert', 'completionpass', ['id' => $data['instance']], MUST_EXIST);
                if ($completionpass) {
                    $errors['gradepass'] = get_string('gradepassrequiredforcompletion', 'mod_assignrecert');
                }
            }
        }

        if ($data['allowsubmissionsfromdate'] && $data['duedate']) {
            if ($data['allowsubmissionsfromdate'] > $data['duedate']) {
                $errors['duedate'] = get_string('duedatevalidation', 'assignrecert');
            }
        }
        if ($data['duedate'] && $data['cutoffdate']) {
            if ($data['duedate'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assignrecert');
            }
        }
        if ($data['allowsubmissionsfromdate'] && $data['cutoffdate']) {
            if ($data['allowsubmissionsfromdate'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'assignrecert');
            }
        }
        if ($data['blindmarking'] && $data['attemptreopenmethod'] == ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_UNTILPASS) {
            $errors['attemptreopenmethod'] = get_string('reopenuntilpassincompatiblewithblindmarking', 'assignrecert');
        }

        // Totara: Verify 'grade to pass' completion setting.
        if (array_key_exists('completion', $data) && $data['completion'] == COMPLETION_TRACKING_AUTOMATIC) {
            $completionpass = isset($data['completionpass']) ? $data['completionpass'] : !empty($this->current->completionpass);

            // Show an error if require passing grade was selected and the grade to pass was set to 0.
            if ($completionpass && (empty($data['gradepass']) || grade_floatval((float)$data['gradepass']) == 0)) {
                if (isset($data['completionpass'])) {
                    $errors['completionpass'] = get_string('gradetopassnotset', 'mod_assignrecert');
                } else {
                    $errors['gradepass'] = get_string('gradetopassmustbeset', 'mod_assignrecert');
                }
            }
        }

        // If you want to use 'Student must receive a grade to complete this activity' then you need to enable either
        // a grade type, or the default grade book feedback plugin. Otherwise, a grade_items record is not created.
        if (empty($data['grade']) &&
            !empty($data['completion']) && $data['completion'] == COMPLETION_TRACKING_AUTOMATIC &&
            !empty($data['completionusegrade']) && $data['completionusegrade'] == 1) {
            $ctx = null;
            if ($this->current && $this->current->coursemodule) {
                $cm = get_coursemodule_from_instance('assignrecert', $this->current->id, 0, false, MUST_EXIST);
                $ctx = context_module::instance($cm->id);
            }
            $assignmentrecert = new assignrecert($ctx, null, null);

            // Get default grade book feedback plugin.
            $adminconfig = $assignmentrecert->get_admin_config();
            $gradebookplugin = $adminconfig->feedback_plugin_for_gradebook;

            if (empty($data[$gradebookplugin . '_enabled'])) {
                $gradebookplugin = str_replace('assignrecertfeedback_', '', $gradebookplugin);
                $plugin = $assignmentrecert->get_feedback_plugin_by_type($gradebookplugin);
                $errors['completionusegrade'] = get_string('completionusegradewithoutgradeenabled', 'assignrecert', $plugin->get_name());
            }
        }

        return $errors;
    }

    /**
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('assignrecert', $this->current->id, 0, false, MUST_EXIST);
            $ctx = context_module::instance($cm->id);
        }
        $assignmentrecert = new assignrecert($ctx, null, null);
        if ($this->current && $this->current->course) {
            if (!$ctx) {
                $ctx = context_course::instance($this->current->course);
            }
            $course = $DB->get_record('course', array('id'=>$this->current->course), '*', MUST_EXIST);
            $assignmentrecert->set_course($course);
        }

        $draftitemid = file_get_submitted_draft_itemid('introattachments');
        file_prepare_draft_area($draftitemid, $ctx->id, 'mod_assignrecert', ASSIGNRECERT_INTROATTACHMENT_FILEAREA,
                                0, array('subdirs' => 0));
        $defaultvalues['introattachments'] = $draftitemid;

        $assignmentrecert->plugin_data_preprocessing($defaultvalues);
    }

    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        $mform =& $this->_form;
        $items = array();

        $mform->addElement('advcheckbox', 'completionsubmit', '', get_string('completionsubmit', 'assignrecert'));
        $items[] = 'completionsubmit';

        // Totara: Add require passing grade
        $mform->addElement('advcheckbox', 'completionpass', get_string('completionpass', 'mod_assignrecert'),
            get_string('completionpass', 'mod_assignrecert'), ['group' => 'cpass']);
        $mform->disabledIf('completionpass', 'completionusegrade', 'notchecked');
        $mform->addHelpButton('completionpass', 'completionpass', 'mod_assignrecert');
        $items[] = 'completionpass';

        return $items;
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        // Totara: Add require passing grade
        return !empty($data['completionsubmit']) || !empty($data['completionpass']);
    }

}
