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
 * Renderable that initialises the grading "app".
 *
 * @package    mod_assignrecert
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assignrecert\output;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use renderable;
use templatable;
use stdClass;

/**
 * Grading app renderable.
 *
 * @package    mod_assignrecert
 * @since      Moodle 3.1
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_app implements templatable, renderable {

    /**
     * @var $userid - The initial user id.
     */
    public $userid = 0;

    /**
     * @var $groupid - The initial group id.
     */
    public $groupid = 0;

    /**
     * @var $assignmentrecert - The assignmentrecert instance.
     */
    public $assignmentrecert = null;

    /**
     * Constructor for this renderable.
     *
     * @param int $userid The user we will open the grading app too.
     * @param int $groupid If groups are enabled this is the current course group.
     * @param assignrecert $assignmentrecert The assignmentrecert class
     */
    public function __construct($userid, $groupid, $assignmentrecert) {
        $this->userid = $userid;
        $this->groupid = $groupid;
        $this->assignmentrecert = $assignmentrecert;
        $this->participants = $assignmentrecert->list_participants_with_filter_status_and_group($groupid);
        if (!$this->userid && count($this->participants)) {
            $this->userid = reset($this->participants)->id;
        }
    }

    /**
     * Export this class data as a flat list for rendering in a template.
     *
     * @param renderer_base $output The current page renderer.
     * @return stdClass - Flat list of exported data.
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $export = new stdClass();
        $export->userid = $this->userid;
        $export->assignmentrecertid = $this->assignmentrecert->get_instance()->id;
        $export->cmid = $this->assignmentrecert->get_course_module()->id;
        $export->contextid = $this->assignmentrecert->get_context()->id;
        $export->groupid = $this->groupid;
        $export->name = $this->assignmentrecert->get_context()->get_context_name();
        $export->courseid = $this->assignmentrecert->get_course()->id;
        $export->participants = array();
        $export->index = null;
        $num = 1;
        foreach ($this->participants as $idx => $record) {
            $user = new stdClass();
            $user->id = $record->id;
            $user->fullname = fullname($record);
            $user->requiregrading = $record->requiregrading;
            $user->grantedextension = $record->grantedextension;
            $user->submitted = $record->submitted;
            if (!empty($record->groupid)) {
                $user->groupid = $record->groupid;
                $user->groupname = $record->groupname;
            }
            if ($record->id == $this->userid) {
                $export->index = $num;
                $user->current = true;
            }
            $export->participants[] = $user;
            $num++;
        }

        $hasfiletypesubmission = false;
        foreach ($this->assignmentrecert->get_submission_plugins() as $plugin) {
            if ($plugin->get_type() === 'file' && $plugin->is_enabled() && $plugin->is_visible() && $plugin->allow_submissions()) {
                $hasfiletypesubmission = true;
                break;
            }
        }

        $showreview = false;
        if ($hasfiletypesubmission) {
            $feedbackplugins = $this->assignmentrecert->get_feedback_plugins();
            foreach ($feedbackplugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible()) {
                    if ($plugin->supports_review_panel()) {
                        $showreview = true;
                    }
                }
            }
        }

        $export->showreview = $showreview;

        $time = time();
        $export->count = count($export->participants);
        $strparam = array('x' => $export->index, 'y' => $export->count);
        $export->xofy = get_string('xofy', 'mod_assignrecert', $strparam);
        $export->coursename = $this->assignmentrecert->get_course_context()->get_context_name();
        $export->caneditsettings = has_capability('mod/assignrecert:addinstance', $this->assignmentrecert->get_context());
        $export->duedate = $this->assignmentrecert->get_instance()->duedate;
        $export->duedatestr = userdate($this->assignmentrecert->get_instance()->duedate);
        $export->duedatedisplay = get_string('duedatecolon', 'mod_assignrecert', $export->duedatestr);

        // Time remaining.
        $due = '';
        if ($export->duedate - $time <= 0) {
            $due = get_string('assignmentisdue', 'assignrecert');
        } else {
            $due = get_string('timeremainingcolon', 'assignrecert', format_time($export->duedate - $time));
        }
        $export->timeremainingstr = $due;

        if ($export->duedate < $time) {
            $export->cutoffdate = $this->assignmentrecert->get_instance()->cutoffdate;
            $cutoffdate = $export->cutoffdate;
            if ($cutoffdate) {
                if ($cutoffdate > $time) {
                    $late = get_string('latesubmissionsaccepted', 'assignrecert', userdate($export->cutoffdate));
                } else {
                    $late = get_string('nomoresubmissionsaccepted', 'assignrecert');
                }
                $export->cutoffdatestr = $late;
            }
        }

        $export->defaultsendnotifications = $this->assignmentrecert->get_instance()->sendstudentnotifications;
        $export->rarrow = $output->rarrow();
        $export->larrow = $output->larrow();
        // List of identity fields to display (the user info will not contain any fields the user cannot view anyway).
        $export->showuseridentity = $CFG->showuseridentity;

        return $export;
    }

}
