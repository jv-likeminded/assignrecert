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


require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

/**
 * Assignment Recert extension dates form
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assignrecert_extension_form extends moodleform {
    /** @var array $instance - The data passed to this form */
    private $instance;

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $params = $this->_customdata;

        // Instance variable is used by the form validation function.
        $instance = $params['instance'];
        $this->instance = $instance;

        // Get the assignmentrecert class.
        $assignrecert = $params['assignrecert'];
        $userlist = $params['userlist'];
        $usercount = 0;
        $usershtml = '';

        $extrauserfields = get_extra_user_fields($assignrecert->get_context());
        foreach ($userlist as $userid) {
            if ($usercount >= 5) {
                $usershtml .= get_string('moreusers', 'assignrecert', count($userlist) - 5);
                break;
            }
            $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

            $usershtml .= $assignrecert->get_renderer()->render(new assignrecert_user_summary($user,
                                                                    $assignrecert->get_course()->id,
                                                                    has_capability('moodle/site:viewfullnames',
                                                                    $assignrecert->get_course_context()),
                                                                    $assignrecert->is_blind_marking(),
                                                                    $assignrecert->get_uniqueid_for_user($user->id),
                                                                    $extrauserfields,
                                                                    !$assignrecert->is_active_user($userid)));
                $usercount += 1;
        }

        $userscount = count($userlist);

        $listusersmessage = get_string('grantextensionforusers', 'assignrecert', $userscount);
        $mform->addElement('header', 'general', $listusersmessage);
        $mform->addElement('static', 'userslist', get_string('selectedusers', 'assignrecert'), $usershtml);

        if ($instance->allowsubmissionsfromdate) {
            $mform->addElement('static', 'allowsubmissionsfromdate', get_string('allowsubmissionsfromdate', 'assignrecert'),
                               userdate($instance->allowsubmissionsfromdate));
        }

        $finaldate = 0;
        if ($instance->duedate) {
            $mform->addElement('static', 'duedate', get_string('duedate', 'assignrecert'), userdate($instance->duedate));
            $finaldate = $instance->duedate;
        }
        if ($instance->cutoffdate) {
            $mform->addElement('static', 'cutoffdate', get_string('cutoffdate', 'assignrecert'), userdate($instance->cutoffdate));
            $finaldate = $instance->cutoffdate;
        }
        $mform->addElement('date_time_selector', 'extensionduedate',
                           get_string('extensionduedate', 'assignrecert'), array('optional'=>true));
        $mform->setDefault('extensionduedate', $finaldate);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'selectedusers');
        $mform->setType('selectedusers', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'action', 'saveextension');
        $mform->setType('action', PARAM_ALPHA);

        $this->add_action_buttons(true, get_string('savechanges', 'assignrecert'));
    }

    /**
     * Perform validation on the extension form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($this->instance->duedate && $data['extensionduedate']) {
            if ($this->instance->duedate > $data['extensionduedate']) {
                $errors['extensionduedate'] = get_string('extensionnotafterduedate', 'assignrecert');
            }
        }
        if ($this->instance->allowsubmissionsfromdate && $data['extensionduedate']) {
            if ($this->instance->allowsubmissionsfromdate > $data['extensionduedate']) {
                $errors['extensionduedate'] = get_string('extensionnotafterfromdate', 'assignrecert');
            }
        }

        return $errors;
    }
}
