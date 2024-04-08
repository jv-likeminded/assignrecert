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
 * This file contains the forms to set the marking workflow for selected submissions.
 *
 * @package   mod_assignrecert
 * @copyright 2013 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/mod/assignrecert/feedback/file/locallib.php');

/**
 * Set marking workflow form.
 *
 * @package   mod_assignrecert
 * @copyright 2013 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assignrecert_batch_set_marking_workflow_state_form extends moodleform {
    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        $mform = $this->_form;
        $params = $this->_customdata;
        $formheader = get_string('batchsetmarkingworkflowstateforusers', 'assignrecert', $params['userscount']);

        $mform->addElement('header', 'general', $formheader);
        $mform->addElement('static', 'userslist', get_string('selectedusers', 'assignrecert'), $params['usershtml']);

        $options = $params['markingworkflowstates'];
        $mform->addElement('select', 'markingworkflowstate', get_string('markingworkflowstate', 'assignrecert'), $options);

        // Don't allow notification to be sent until in "Released" state.
        $mform->addElement('selectyesno', 'sendstudentnotifications', get_string('sendstudentnotifications', 'assignrecert'));
        $mform->setDefault('sendstudentnotifications', 0);
        $mform->disabledIf('sendstudentnotifications', 'markingworkflowstate', 'neq', ASSIGNRECERT_MARKING_WORKFLOW_STATE_RELEASED);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'setbatchmarkingworkflowstate');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'selectedusers');
        $mform->setType('selectedusers', PARAM_SEQUENCE);
        $this->add_action_buttons(true, get_string('savechanges'));

    }

    /**
     * Validate the submitted form data.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // As the implementation of this feature exists currently, no user will see a validation
        // failure from this form, but this check ensures the form won't validate if someone
        // manipulates the 'sendstudentnotifications' field's disabled attribute client-side.
        if (!empty($data['sendstudentnotifications']) && $data['markingworkflowstate'] != ASSIGNRECERT_MARKING_WORKFLOW_STATE_RELEASED) {
            $errors['sendstudentnotifications'] = get_string('studentnotificationworkflowstateerror', 'assignrecert');
        }

        return $errors;
    }
}
