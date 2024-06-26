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
 * Assignment Recert grading options form
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assignrecert_grading_options_form extends moodleform {
    /**
     * Define this form - called from the parent constructor.
     */
    public function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata;
        $dirtyclass = array('class' => 'ignoredirty');

        $mform->addElement('header', 'general', get_string('gradingoptions', 'assignrecert'));
        // Visible elements.
        $options = array(-1 => get_string('all'), 10 => '10', 20 => '20', 50 => '50', 100 => '100');
        $maxperpage = get_config('assignrecert', 'maxperpage');
        if (isset($maxperpage) && $maxperpage != -1) {
            unset($options[-1]);
            foreach ($options as $val) {
                if ($val > $maxperpage) {
                    unset($options[$val]);
                }
            }
        }
        $mform->addElement('select', 'perpage', get_string('assignmentrecertsperpage', 'assignrecert'), $options, $dirtyclass);
        $options = array('' => get_string('filternone', 'assignrecert'),
                         ASSIGNRECERT_FILTER_NOT_SUBMITTED => get_string('filternotsubmitted', 'assignrecert'),
                         ASSIGNRECERT_FILTER_SUBMITTED => get_string('filtersubmitted', 'assignrecert'),
                         ASSIGNRECERT_FILTER_REQUIRE_GRADING => get_string('filterrequiregrading', 'assignrecert'),
                         ASSIGNRECERT_FILTER_GRANTED_EXTENSION => get_string('filtergrantedextension', 'assignrecert'));
        if ($instance['submissionsenabled']) {
            $mform->addElement('select', 'filter', get_string('filter', 'assignrecert'), $options, $dirtyclass);
        }
        if (!empty($instance['markingallocationopt'])) {
            $markingfilter = get_string('markerfilter', 'assignrecert');
            $mform->addElement('select', 'markerfilter', $markingfilter, $instance['markingallocationopt'], $dirtyclass);
        }
        if (!empty($instance['markingworkflowopt'])) {
            $workflowfilter = get_string('workflowfilter', 'assignrecert');
            $mform->addElement('select', 'workflowfilter', $workflowfilter, $instance['markingworkflowopt'], $dirtyclass);
        }
        // Quickgrading.
        if ($instance['showquickgrading']) {
            $mform->addElement('checkbox', 'quickgrading', get_string('quickgrading', 'assignrecert'), '', $dirtyclass);
            $mform->addHelpButton('quickgrading', 'quickgrading', 'assignrecert');
            $mform->setDefault('quickgrading', $instance['quickgrading']);
        }

        // Show active/suspended user option.
        if ($instance['showonlyactiveenrolopt']) {
            $mform->addElement('checkbox', 'showonlyactiveenrol', get_string('showonlyactiveenrol', 'grades'), '', $dirtyclass);
            $mform->addHelpButton('showonlyactiveenrol', 'showonlyactiveenrol', 'grades');
            $mform->setDefault('showonlyactiveenrol', $instance['showonlyactiveenrol']);
        }

        // Place student downloads in seperate folders.
        if ($instance['submissionsenabled']) {
            $mform->addElement('checkbox', 'downloadasfolders', get_string('downloadasfolders', 'assignrecert'), '', $dirtyclass);
            $mform->addHelpButton('downloadasfolders', 'downloadasfolders', 'assignrecert');
            $mform->setDefault('downloadasfolders', $instance['downloadasfolders']);
        }

        // Hidden params.
        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'id', $instance['cm']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'saveoptions');
        $mform->setType('action', PARAM_ALPHA);

        // Buttons.
        $this->add_action_buttons(false, get_string('updatetable', 'assignrecert'));
    }
}

