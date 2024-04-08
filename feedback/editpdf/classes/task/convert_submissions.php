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
 * A scheduled task.
 *
 * @package    assignrecertfeedback_editpdf
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignrecertfeedback_editpdf\task;

use core\task\scheduled_task;
use assignrecertfeedback_editpdf\document_services;
use context_module;
use assignrecert;

/**
 * Simple task to convert submissions to pdf in the background.
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class convert_submissions extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('preparesubmissionsforannotation', 'assignrecertfeedback_editpdf');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

        $records = $DB->get_records('assignrecertfeedback_editpdf_queue');

        $assignmentcache = array();

        foreach ($records as $record) {
            $submissionid = $record->submissionid;
            $submission = $DB->get_record('assignrecert_submission', array('id' => $submissionid), '*', IGNORE_MISSING);
            if (!$submission) {
                // Submission no longer exists.
                $DB->delete_records('assignrecertfeedback_editpdf_queue', array('id' => $record->id));
                continue;
            }

            $assignmentrecertid = $submission->assignmentrecert;
            $attemptnumber = $record->submissionattempt;

            if (empty($assignmentcache[$assignmentrecertid])) {
                $cm = get_coursemodule_from_instance('assignrecert', $assignmentrecertid, 0, false, MUST_EXIST);
                $context = context_module::instance($cm->id);

                $assignmentrecert = new assignrecert($context, null, null);
                $assignmentcache[$assignmentrecertid] = $assignmentrecert;
            } else {
                $assignmentrecert = $assignmentcache[$assignmentrecertid];
            }

            $users = array();
            if ($submission->userid) {
                array_push($users, $submission->userid);
            } else {
                $members = $assignmentrecert->get_submission_group_members($submission->groupid, true);

                foreach ($members as $member) {
                    array_push($users, $member->id);
                }
            }

            mtrace('Convert ' . count($users) . ' submission attempt(s) for assignmentrecert ' . $assignmentrecertid);
            foreach ($users as $userid) {
                try {
                    document_services::get_page_images_for_attempt($assignmentrecert,
                                                                   $userid,
                                                                   $attemptnumber,
                                                                   true);
                    document_services::get_page_images_for_attempt($assignmentrecert,
                                                                   $userid,
                                                                   $attemptnumber,
                                                                   false);
                } catch (\moodle_exception $e) {
                    mtrace('Conversion failed with error:' . $e->errorcode);
                }
            }

            $DB->delete_records('assignrecertfeedback_editpdf_queue', array('id' => $record->id));
        }
    }

}
