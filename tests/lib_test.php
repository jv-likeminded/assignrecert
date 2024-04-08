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
 * Unit tests for (some of) mod/assignrecert/lib.php.
 *
 * @package    mod_assignrecert
 * @category   phpunit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assignrecert/lib.php');
require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
require_once($CFG->dirroot . '/mod/assignrecert/tests/generator.php');

/**
 * Unit tests for (some of) mod/assignrecert/lib.php.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_assignrecert_lib_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_assignrecert_test_generator;

    public function test_assignrecert_print_overview() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        // Assignment Recert with default values.
        $firstassignrecert = $this->create_instance($course, ['name' => 'First Assignment Recert']);

        // Assignment Recert with submissions.
        $secondassignrecert = $this->create_instance($course, [
                'name' => 'Assignment Recert with submissions',
                'duedate' => time(),
                'attemptreopenmethod' => ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts' => 3,
                'submissiondrafts' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);
        $this->add_submission($student, $secondassign);
        $this->submit_for_grading($student, $secondassign);
        $this->mark_submission($teacher, $secondassign, $student, 50.0);

        // Past assignmentrecerts should not show up.
        $pastassignrecert = $this->create_instance($course, [
                'name' => 'Past Assignment Recert',
                'duedate' => time() - DAYSECS - 1,
                'cutoffdate' => time() - DAYSECS,
                'nosubmissions' => 0,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        // Open assignmentrecerts should show up only if relevant.
        $openassignrecert = $this->create_instance($course, [
                'name' => 'Open Assignment Recert',
                'duedate' => time(),
                'cutoffdate' => time() + DAYSECS,
                'nosubmissions' => 0,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);
        $pastsubmission = $pastassign->get_user_submission($student->id, true);
        $opensubmission = $openassign->get_user_submission($student->id, true);

        // Check the overview as the different users.
        // For students , open assignmentrecerts should show only when there are no valid submissions.
        $this->setUser($student);
        $overview = array();
        $courses = $DB->get_records('course', array('id' => $course->id));
        assignrecert_print_overview($courses, $overview);
        $this->assertEquals(1, count($overview));
        $this->assertMatchesRegularExpression('/.*Open Assignment Recert.*/', $overview[$course->id]['assignrecert']); // No valid submission.
        $this->assertDoesNotMatchRegularExpression('/.*First Assignment Recert.*/', $overview[$course->id]['assignrecert']); // Has valid submission.

        // And now submit the submission.
        $opensubmission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $openassign->testable_update_submission($opensubmission, $student->id, true, false);

        $overview = array();
        assignrecert_print_overview($courses, $overview);
        $this->assertEquals(0, count($overview));

        $this->setUser($teacher);
        $overview = array();
        assignrecert_print_overview($courses, $overview);
        $this->assertEquals(1, count($overview));
        // Submissions without a grade.
        $this->assertMatchesRegularExpression('/.*Open Assignment Recert.*/', $overview[$course->id]['assignrecert']);
        $this->assertDoesNotMatchRegularExpression('/.*Assignment Recert with submissions.*/', $overview[$course->id]['assignrecert']);

        $this->setUser($teacher);
        $overview = array();
        assignrecert_print_overview($courses, $overview);
        $this->assertEquals(1, count($overview));
        // Submissions without a grade.
        $this->assertMatchesRegularExpression('/.*Open Assignment Recert.*/', $overview[$course->id]['assignrecert']);
        $this->assertDoesNotMatchRegularExpression('/.*Assignment Recert with submissions.*/', $overview[$course->id]['assignrecert']);

        // Let us grade a submission.
        $this->setUser($teacher);
        $data = new stdClass();
        $data->grade = '50.0';
        $openassign->testable_apply_grade_to_user($data, $student->id, 0);

        // The assignrecert_print_overview expects the grade date to be after the submission date.
        $graderecord = $DB->get_record('assignrecert_grades', array('assignmentrecert' => $openassign->get_instance()->id,
            'userid' => $student->id, 'attemptnumber' => 0));
        $graderecord->timemodified += 1;
        $DB->update_record('assignrecert_grades', $graderecord);

        $overview = array();
        assignrecert_print_overview($courses, $overview);
        // Now assignmentrecert 4 should not show up.
        $this->assertEmpty($overview);

        $this->setUser($teacher);
        $overview = array();
        assignrecert_print_overview($courses, $overview);
        // Now assignmentrecert 4 should not show up.
        $this->assertEmpty($overview);
    }

    /**
     * Test that assignrecert_print_overview does not return any assignmentrecerts which are Open Offline.
     */
    public function test_assignrecert_print_overview_open_offline() {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();
        $openassignrecert = $this->create_instance($course, [
                'duedate' => time() + DAYSECS,
                'cutoffdate' => time() + (DAYSECS * 2),
            ]);

        $this->setUser($student);
        $overview = [];
        assignrecert_print_overview([$course], $overview);

        $this->assertDebuggingCalledCount(0);
        $this->assertEquals(0, count($overview));
    }

    public function test_assignrecert_get_recent_mod_activity() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course);
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        $index = 1;
        $activities = [
            $index => (object) [
                'type' => 'assignrecert',
                'cmid' => $assignrecert->get_course_module()->id,
            ],
        ];

        $this->setUser($teacher);
        assignrecert_get_recent_mod_activity($activities, $index, time() - HOURSECS, $course->id, $assignrecert->get_course_module()->id);

        $activity = $activities[1];
        $this->assertEquals("assignrecert", $activity->type);
        $this->assertEquals($student->id, $activity->user->id);
    }

    /**
     * Ensure that assignrecert_user_complete displays information about drafts.
     */
    public function test_assignrecert_user_complete() {
        global $PAGE, $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course, ['submissiondrafts' => 1]);
        $this->add_submission($student, $assignrecert);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', array('id' => $assignrecert->get_course_module()->id)));

        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_DRAFT;
        $DB->update_record('assignrecert_submission', $submission);

        $this->expectOutputRegex('/Draft/');
        assignrecert_user_complete($course, $student, $assignrecert->get_course_module(), $assignrecert->get_instance());
    }

    /**
     * Ensure that assignrecert_user_outline fetches updated grades.
     */
    public function test_assignrecert_user_outline() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        $this->setUser($teacher);
        $data = $assignrecert->get_user_grade($student->id, true);
        $data->grade = '50.5';
        $assignrecert->update_grade($data);

        $result = assignrecert_user_outline($course, $student, $assignrecert->get_course_module(), $assignrecert->get_instance());

        $this->assertMatchesRegularExpression('/50.5/', $result->info);
    }

    /**
     * Ensure that assignrecert_get_completion_state reflects the correct status at each point.
     */
    public function test_assignrecert_get_completion_state() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course, [
                'submissiondrafts' => 0,
                'completionsubmit' => 1
            ]);

        $this->setUser($student);
        $result = assignrecert_get_completion_state($course, $assignrecert->get_course_module(), $student->id, false);
        $this->assertFalse($result);

        $this->add_submission($student, $assignrecert);
        $result = assignrecert_get_completion_state($course, $assignrecert->get_course_module(), $student->id, false);
        $this->assertFalse($result);

        $this->submit_for_grading($student, $assignrecert);
        $result = assignrecert_get_completion_state($course, $assignrecert->get_course_module(), $student->id, false);
        $this->assertTrue($result);

        $this->mark_submission($teacher, $assignrecert, $student, 50.0);
        $result = assignrecert_get_completion_state($course, $assignrecert->get_course_module(), $student->id, false);
        $this->assertTrue($result);
    }

    /**
     * Tests for mod_assignrecert_refresh_events.
     */
    public function test_assignrecert_refresh_events() {
        global $DB;


        $duedate = time();
        $newduedate = $duedate + DAYSECS;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course, [
                'duedate' => $duedate,
            ]);

        $instance = $assignrecert->get_instance();
        $eventparams = ['modulename' => 'assignrecert', 'instance' => $instance->id];

        // Make sure the calendar event for assignmentrecert 1 matches the initial due date.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $duedate);

        // Manually update assignmentrecert 1's due date.
        $DB->update_record('assignrecert', (object) ['id' => $instance->id, 'duedate' => $newduedate]);

        // Then refresh the assignmentrecert events of assignmentrecert 1's course.
        $this->assertTrue(assignrecert_refresh_events($course->id));

        // Confirm that the assignmentrecert 1's due date event now has the new due date after refresh.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // Create a second course and assignmentrecert.
        $othercourse = $this->getDataGenerator()->create_course();;
        $otherassignrecert = $this->create_instance($othercourse, ['duedate' => $duedate, 'course' => $othercourse->id]);
        $otherinstance = $otherassign->get_instance();

        // Manually update assignmentrecert 1 and 2's due dates.
        $newduedate += DAYSECS;
        $DB->update_record('assignrecert', (object)['id' => $instance->id, 'duedate' => $newduedate]);
        $DB->update_record('assignrecert', (object)['id' => $otherinstance->id, 'duedate' => $newduedate]);

        // Refresh events of all courses.
        $this->assertTrue(assignrecert_refresh_events());

        // Check the due date calendar event for assignmentrecert 1.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // Check the due date calendar event for assignmentrecert 2.
        $eventparams['instance'] = $otherinstance->id;
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // In case the course ID is passed as a numeric string.
        $this->assertTrue(assignrecert_refresh_events('' . $course->id));

        // Non-existing course ID.
        $this->assertFalse(assignrecert_refresh_events(-1));

        // Invalid course ID.
        $this->assertFalse(assignrecert_refresh_events('aaa'));
    }
}
