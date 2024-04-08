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
 * Contains the event tests for the module assignrecert.
 *
 * @package   mod_assignrecert
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assignrecert/tests/generator.php');
require_once($CFG->dirroot . '/mod/assignrecert/tests/fixtures/event_mod_assignrecert_fixtures.php');
require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

/**
 * Contains the event tests for the module assignrecert.
 *
 * @package   mod_assignrecert
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignrecert_events_testcase extends advanced_testcase {
    // Use the generator helper.
    use mod_assignrecert_test_generator;

    /**
     * Basic tests for the submission_created() abstract class.
     */
    public function test_base_event() {

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert');
        $instance = $generator->create_instance(array('course' => $course->id));
        $modcontext = context_module::instance($instance->cmid);

        $data = array(
            'context' => $modcontext,
        );

        $event = \mod_assignrecert_unittests\event\nothing_happened::create($data);
        $assignrecert = $event->get_assignrecert();
        $this->assertDebuggingCalled();
        $this->assertInstanceOf('assignrecert', $assignrecert);

        $event = \mod_assignrecert_unittests\event\nothing_happened::create($data);
        $event->set_assignrecert($assignrecert);
        $assign2 = $event->get_assignrecert();
        $this->assertDebuggingNotCalled();
        $this->assertSame($assignrecert, $assign2);
    }

    /**
     * Basic tests for the submission_created() abstract class.
     */
    public function test_submission_created() {

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert');
        $instance = $generator->create_instance(array('course' => $course->id));
        $modcontext = context_module::instance($instance->cmid);

        // Standard Event parameters.
        $params = array(
            'context' => $modcontext,
            'courseid' => $course->id
        );

        $eventinfo = $params;
        $eventinfo['other'] = array(
            'submissionid' => '17',
            'submissionattempt' => 0,
            'submissionstatus' => 'submitted'
        );

        $sink = $this->redirectEvents();
        $event = \mod_assignrecert_unittests\event\submission_created::create($eventinfo);
        $event->trigger();
        $result = $sink->get_events();
        $event = reset($result);
        $sink->close();

        $this->assertEquals($modcontext->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);

        // Check that an error occurs when teamsubmission is not set.
        try {
            \mod_assignrecert_unittests\event\submission_created::create($params);
            $this->fail('Other must contain the key submissionid.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        // Check that the submission status debugging is fired.
        $subinfo = $params;
        $subinfo['other'] = array('submissionid' => '23');
        try {
            \mod_assignrecert_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionattempt.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }

        $subinfo['other'] = array('submissionattempt' => '0');
        try {
            \mod_assignrecert_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionstatus.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
    }

    /**
     * Basic tests for the submission_updated() abstract class.
     */
    public function test_submission_updated() {

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert');
        $instance = $generator->create_instance(array('course' => $course->id));
        $modcontext = context_module::instance($instance->cmid);

        // Standard Event parameters.
        $params = array(
            'context' => $modcontext,
            'courseid' => $course->id
        );

        $eventinfo = $params;
        $eventinfo['other'] = array(
            'submissionid' => '17',
            'submissionattempt' => 0,
            'submissionstatus' => 'submitted'
        );

        $sink = $this->redirectEvents();
        $event = \mod_assignrecert_unittests\event\submission_updated::create($eventinfo);
        $event->trigger();
        $result = $sink->get_events();
        $event = reset($result);
        $sink->close();

        $this->assertEquals($modcontext->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);

        // Check that an error occurs when teamsubmission is not set.
        try {
            \mod_assignrecert_unittests\event\submission_created::create($params);
            $this->fail('Other must contain the key submissionid.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        // Check that the submission status debugging is fired.
        $subinfo = $params;
        $subinfo['other'] = array('submissionid' => '23');
        try {
            \mod_assignrecert_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionattempt.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }

        $subinfo['other'] = array('submissionattempt' => '0');
        try {
            \mod_assignrecert_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionstatus.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
    }

    public function test_extension_granted() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $now = time();
        $tomorrow = $now + DAYSECS;
        $yesterday = $now - DAYSECS;

        $assignrecert = $this->create_instance($course, [
            'duedate' => $yesterday,
            'cutoffdate' => $yesterday,
        ]);
        $sink = $this->redirectEvents();

        $assignrecert->testable_save_user_extension($student->id, $tomorrow);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\extension_granted', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);

        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'grant extension',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            $student->id,
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_locked() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);
        $sink = $this->redirectEvents();

        $assignrecert->lock_submission($student->id);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\submission_locked', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'lock submission',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('locksubmissionforstudent', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student))),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_identities_revealed() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course, ['blindmarking' => 1]);
        $sink = $this->redirectEvents();

        $assignrecert->reveal_identities();

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\identities_revealed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'reveal identities',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('revealidentities', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    /**
     * Test the submission_status_viewed event.
     */
    public function test_submission_status_viewed() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/mod/assignrecert/view.php');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\submission_status_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewownsubmissionstatus', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_submission_status_updated() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);
        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, false);

        $sink = $this->redirectEvents();
        $assignrecert->revert_to_draft($student->id);

        $events = $sink->get_events();
        $this->assertCount(2, $events);
        $event = $events[1];
        $this->assertInstanceOf('\mod_assignrecert\event\submission_status_updated', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($submission->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals(ASSIGNRECERT_SUBMISSION_STATUS_DRAFT, $event->other['newstatus']);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'revert submission to draft',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('reverttodraftforstudent', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student))),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_marker_updated() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);

        $sink = $this->redirectEvents();
        $assignrecert->testable_process_set_batch_marking_allocation($student->id, $teacher->id);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\marker_updated', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals($teacher->id, $event->other['markerid']);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'set marking allocation',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('setmarkerallocationforlog', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student), 'marker' => fullname($teacher))),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_workflow_state_updated() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);

        // Test process_set_batch_marking_workflow_state.
        $sink = $this->redirectEvents();
        $assignrecert->testable_process_set_batch_marking_workflow_state($student->id, ASSIGNRECERT_MARKING_WORKFLOW_STATE_INREVIEW);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\workflow_state_updated', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals(ASSIGNRECERT_MARKING_WORKFLOW_STATE_INREVIEW, $event->other['newstate']);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'set marking workflow state',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('setmarkingworkflowstateforlog', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student), 'state' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_INREVIEW)),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Test setting workflow state in apply_grade_to_user.
        $sink = $this->redirectEvents();
        $data = new stdClass();
        $data->grade = '50.0';
        $data->workflowstate = 'readyforrelease';
        $assignrecert->testable_apply_grade_to_user($data, $student->id, 0);

        $events = $sink->get_events();

        // TOTARA: has two events called here, both of type totara_core\event\module_completion
        $moodleevents = [];
        $totaraevents = [];
        foreach ($events as $key => $event) {
            if ($event instanceof \totara_core\event\module_completion) {
                $totaraevents[] = $event;
            } else {
                $moodleevents[] = $event;
            }
        }
        $events = $moodleevents;

        $this->assertCount(4, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\workflow_state_updated', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals(ASSIGNRECERT_MARKING_WORKFLOW_STATE_READYFORRELEASE, $event->other['newstate']);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'set marking workflow state',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('setmarkingworkflowstateforlog', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student), 'state' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_READYFORRELEASE)),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Test setting workflow state in process_save_quick_grades.
        $sink = $this->redirectEvents();

        $data = array(
            'grademodified_' . $student->id => time(),
            'gradeattempt_' . $student->id => '',
            'quickgrade_' . $student->id => '60.0',
            'quickgrade_' . $student->id . '_workflowstate' => 'inmarking'
        );
        $assignrecert->testable_process_save_quick_grades($data);

        $events = $sink->get_events();

        // TOTARA: has two events called here, both of type totara_core\event\module_completion
        $moodleevents = [];
        $totaraevents = [];
        foreach ($events as $key => $event) {
            if ($event instanceof \totara_core\event\module_completion) {
                $totaraevents[] = $event;
            } else {
                $moodleevents[] = $event;
            }
        }
        $events = $moodleevents;

        $this->assertCount(4, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\workflow_state_updated', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals(ASSIGNRECERT_MARKING_WORKFLOW_STATE_INMARKING, $event->other['newstate']);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'set marking workflow state',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('setmarkingworkflowstateforlog', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student), 'state' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_INMARKING)),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_duplicated() {

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        $assignrecert = $this->create_instance($course);
        $submission1 = $assignrecert->get_user_submission($student->id, true, 0);
        $submission2 = $assignrecert->get_user_submission($student->id, true, 1);
        $submission2->status = ASSIGNRECERT_SUBMISSION_STATUS_REOPENED;
        $assignrecert->testable_update_submission($submission2, $student->id, time(), $assignrecert->get_instance()->teamsubmission);

        $sink = $this->redirectEvents();
        $notices = null;
        $assignrecert->copy_previous_attempt($notices);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\submission_duplicated', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($submission2->id, $event->objectid);
        $this->assertEquals($student->id, $event->userid);
        $submission2->status = ASSIGNRECERT_SUBMISSION_STATUS_DRAFT;
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'submissioncopied',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            $assignrecert->testable_format_submission_for_log($submission2),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_unlocked() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);
        $sink = $this->redirectEvents();

        $assignrecert->unlock_submission($student->id);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\submission_unlocked', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'unlock submission',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('unlocksubmissionforstudent', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student))),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Now test as the student. Confirm that they can also unlock the submission, because it's an internal API.
        $this->setUser($student);
        $sink = $this->redirectEvents();
        $assignrecert->unlock_submission($student->id);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_assignrecert\event\submission_unlocked', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($assignrecert->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $string_params = [
            'id' => $student->id,
            'fullname' => fullname($student)
        ];
        $expected = [
            $assignrecert->get_course()->id,
            'assignrecert',
            'unlock submission',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('unlocksubmissionforstudent', 'assignrecert', $string_params),
            $assignrecert->get_course_module()->id
        ];
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_graded() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);

        // Test apply_grade_to_user.
        $sink = $this->redirectEvents();

        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $student->id, 0);
        $grade = $assignrecert->get_user_grade($student->id, false, 0);

        $events = $sink->get_events();

        // TOTARA: has two events called here, both of type totara_core\event\module_completion
        $moodleevents = [];
        $totaraevents = [];
        foreach ($events as $key => $event) {
            if ($event instanceof \totara_core\event\module_completion) {
                $totaraevents[] = $event;
            } else {
                $moodleevents[] = $event;
            }
        }
        $events = $moodleevents;

        $this->assertCount(3, $events);
        $event = $events[2];
        $this->assertInstanceOf('\mod_assignrecert\event\submission_graded', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($grade->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'grade submission',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            $assignrecert->format_grade_for_log($grade),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Test process_save_quick_grades.
        $sink = $this->redirectEvents();

        $grade = $assignrecert->get_user_grade($student->id, false);
        $data = array(
            'grademodified_' . $student->id => time(),
            'gradeattempt_' . $student->id => $grade->attemptnumber,
            'quickgrade_' . $student->id => '60.0'
        );
        $assignrecert->testable_process_save_quick_grades($data);
        $grade = $assignrecert->get_user_grade($student->id, false);
        // TOTARA: required as string comparison of numeric values no longer coerces type.
        $this->assertSame('60.00000', $grade->grade);

        $events = $sink->get_events();

        // TOTARA: has two events called here, both of type totara_core\event\module_completion
        $moodleevents = [];
        $totaraevents = [];
        foreach ($events as $key => $event) {
            if ($event instanceof \totara_core\event\module_completion) {
                $totaraevents[] = $event;
            } else {
                $moodleevents[] = $event;
            }
        }
        $events = $moodleevents;

        $this->assertCount(3, $events);
        $event = $events[2];
        $this->assertInstanceOf('\mod_assignrecert\event\submission_graded', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($grade->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'grade submission',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            $assignrecert->format_grade_for_log($grade),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Test update_grade.
        $sink = $this->redirectEvents();
        $data = clone($grade);
        $data->grade = '50.0';
        $assignrecert->update_grade($data);
        $grade = $assignrecert->get_user_grade($student->id, false, 0);
        // TOTARA: required as string comparison of numeric values no longer coerces type.
        $this->assertSame('50.00000', $grade->grade);
        $events = $sink->get_events();

        // TOTARA: has two events called here, both of type totara_core\event\module_completion
        $moodleevents = [];
        $totaraevents = [];
        foreach ($events as $key => $event) {
            if ($event instanceof \totara_core\event\module_completion) {
                $totaraevents[] = $event;
            } else {
                $moodleevents[] = $event;
            }
        }
        $events = $moodleevents;

        $this->assertCount(3, $events);
        $event = $events[2];
        $this->assertInstanceOf('\mod_assignrecert\event\submission_graded', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($grade->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'grade submission',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            $assignrecert->format_grade_for_log($grade),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    /**
     * Test the submission_viewed event.
     */
    public function test_submission_viewed() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);
        $submission = $assignrecert->get_user_submission($student->id, true);

        // We need to set the URL in order to view the submission.
        $PAGE->set_url('/mod/assignrecert/view.php');
        // A hack - these variables are used by the view_plugin_content function to
        // determine what we actually want to view - would usually be set in URL.
        global $_POST;
        $_POST['plugin'] = 'comments';
        $_POST['sid'] = $submission->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view('viewpluginassignrecertsubmission');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\submission_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($submission->id, $event->objectid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view submission',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewsubmissionforuser', 'assignrecert', $student->id),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the feedback_viewed event.
     */
    public function test_feedback_viewed() {
        global $DB, $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);
        $submission = $assignrecert->get_user_submission($student->id, true);

        // Insert a grade for this submission.
        $grade = new stdClass();
        $grade->assignmentrecert = $assignrecert->get_instance()->id;
        $grade->userid = $student->id;
        $gradeid = $DB->insert_record('assignrecert_grades', $grade);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/mod/assignrecert/view.php');
        // A hack - these variables are used by the view_plugin_content function to
        // determine what we actually want to view - would usually be set in URL.
        global $_POST;
        $_POST['plugin'] = 'comments';
        $_POST['gid'] = $gradeid;
        $_POST['sid'] = $submission->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view('viewpluginassignrecertfeedback');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\feedback_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEquals($gradeid, $event->objectid);
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view feedback',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewfeedbackforuser', 'assignrecert', $student->id),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the grading_form_viewed event.
     */
    public function test_grading_form_viewed() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/mod/assignrecert/view.php');
        // A hack - this variable is used by the view_single_grade_page function.
        global $_POST;
        $_POST['rownum'] = 1;
        $_POST['userid'] = $student->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view('grade');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\grading_form_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view grading form',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewgradingformforstudent', 'assignrecert', array('id' => $student->id,
                'fullname' => fullname($student))),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the grading_table_viewed event.
     */
    public function test_grading_table_viewed() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/mod/assignrecert/view.php');
        // A hack - this variable is used by the view_single_grade_page function.
        global $_POST;
        $_POST['rownum'] = 1;
        $_POST['userid'] = $student->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view('grading');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\grading_table_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view submission grading table',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewsubmissiongradingtable', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the submission_form_viewed event.
     */
    public function test_submission_form_viewed() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        $assignrecert = $this->create_instance($course);

        // We need to set the URL in order to view the submission form.
        $PAGE->set_url('/mod/assignrecert/view.php');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view('editsubmission');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\submission_form_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view submit assignmentrecert form',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('editsubmission', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the submission_form_viewed event.
     */
    public function test_submission_confirmation_form_viewed() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        $assignrecert = $this->create_instance($course);

        // We need to set the URL in order to view the submission form.
        $PAGE->set_url('/mod/assignrecert/view.php');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view('submit');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\submission_confirmation_form_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view confirm submit assignment form',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewownsubmissionform', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the reveal_identities_confirmation_page_viewed event.
     */
    public function test_reveal_identities_confirmation_page_viewed() {
        global $PAGE;

        // Set to the admin user so we have the permission to reveal identities.
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->create_instance($course);

        // We need to set the URL in order to view the submission form.
        $PAGE->set_url('/mod/assignrecert/view.php');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->view('revealidentities');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\reveal_identities_confirmation_page_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewrevealidentitiesconfirm', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the statement_accepted event.
     */
    public function test_statement_accepted() {
        // We want to be a student so we can submit assignmentrecerts.

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        // We do not want to send any messages to the student during the PHPUNIT test.
        set_config('submissionreceipts', false, 'assignrecert');

        $assignrecert = $this->create_instance($course);

        // Create the data we want to pass to the submit_for_grading function.
        $data = new stdClass();
        $data->submissionstatement = 'We are the Borg. You will be assimilated. Resistance is futile. - do you agree
            to these terms?';

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->submit_for_grading($data, array());
        $events = $sink->get_events();

        // TOTARA: has two events called here, both of type totara_core\event\module_completion
        $moodleevents = [];
        $totaraevents = [];
        foreach ($events as $key => $event) {
            if ($event instanceof \totara_core\event\module_completion) {
                $totaraevents[] = $event;
            } else {
                $moodleevents[] = $event;
            }
        }
        $events = $moodleevents;

        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\statement_accepted', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'submission statement accepted',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('submissionstatementacceptedlog',
                'mod_assignrecert',
                fullname($student)),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        // Enable the online text submission plugin.
        $submissionplugins = $assignrecert->get_submission_plugins();
        foreach ($submissionplugins as $plugin) {
            if ($plugin->get_type() === 'onlinetext') {
                $plugin->enable();
                break;
            }
        }

        // Create the data we want to pass to the save_submission function.
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => 'Online text',
            'format' => FORMAT_HTML,
            'itemid' => file_get_unused_draft_itemid()
        );
        $data->submissionstatement = 'We are the Borg. You will be assimilated. Resistance is futile. - do you agree
            to these terms?';

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->save_submission($data, $notices);
        $events = $sink->get_events();
        $event = $events[2];

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\statement_accepted', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the batch_set_workflow_state_viewed event.
     */
    public function test_batch_set_workflow_state_viewed() {

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->testable_view_batch_set_workflow_state($student->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\batch_set_workflow_state_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view batch set marking workflow state',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewbatchsetmarkingworkflowstate', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the batch_set_marker_allocation_viewed event.
     */
    public function test_batch_set_marker_allocation_viewed() {

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->testable_view_batch_markingallocation($student->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assignrecert\event\batch_set_marker_allocation_viewed', $event);
        $this->assertEquals($assignrecert->get_context(), $event->get_context());
        $expected = array(
            $assignrecert->get_course()->id,
            'assignrecert',
            'view batch set marker allocation',
            'view.php?id=' . $assignrecert->get_course_module()->id,
            get_string('viewbatchmarkingallocation', 'assignrecert'),
            $assignrecert->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the user override created event.
     *
     * There is no external API for creating a user override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_user_override_created() {

        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'relateduserid' => 2,
            'context' => context_module::instance($assignrecert->cmid),
            'other' => array(
                'assignrecertid' => $assignrecert->id
            )
        );
        $event = \mod_assignrecert\event\user_override_created::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_assignrecert\event\user_override_created', $event);
        $this->assertEquals(context_module::instance($assignrecert->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the group override created event.
     *
     * There is no external API for creating a group override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_group_override_created() {

        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'context' => context_module::instance($assignrecert->cmid),
            'other' => array(
                'assignrecertid' => $assignrecert->id,
                'groupid' => 2
            )
        );
        $event = \mod_assignrecert\event\group_override_created::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_assignrecert\event\group_override_created', $event);
        $this->assertEquals(context_module::instance($assignrecert->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the user override updated event.
     *
     * There is no external API for updating a user override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_user_override_updated() {

        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'relateduserid' => 2,
            'context' => context_module::instance($assignrecert->cmid),
            'other' => array(
                'assignrecertid' => $assignrecert->id
            )
        );
        $event = \mod_assignrecert\event\user_override_updated::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_assignrecert\event\user_override_updated', $event);
        $this->assertEquals(context_module::instance($assignrecert->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the group override updated event.
     *
     * There is no external API for updating a group override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_group_override_updated() {

        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'context' => context_module::instance($assignrecert->cmid),
            'other' => array(
                'assignrecertid' => $assignrecert->id,
                'groupid' => 2
            )
        );
        $event = \mod_assignrecert\event\group_override_updated::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_assignrecert\event\group_override_updated', $event);
        $this->assertEquals(context_module::instance($assignrecert->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the user override deleted event.
     */
    public function test_user_override_deleted() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $assignrecertinstance = $this->getDataGenerator()->create_module('assignrecert', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('assignrecert', $assignrecertinstance->id, $course->id);
        $context = context_module::instance($cm->id);
        $assignrecert = new assignrecert($context, $cm, $course);

        // Create an override.
        $override = new stdClass();
        $override->assignrecert = $assignrecertinstance->id;
        $override->userid = 2;
        $override->id = $DB->insert_record('assignrecert_overrides', $override);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->delete_override($override->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_assignrecert\event\user_override_deleted', $event);
        $this->assertEquals(context_module::instance($cm->id), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the group override deleted event.
     */
    public function test_group_override_deleted() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $assignrecertinstance = $this->getDataGenerator()->create_module('assignrecert', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('assignrecert', $assignrecertinstance->id, $course->id);
        $context = context_module::instance($cm->id);
        $assignrecert = new assignrecert($context, $cm, $course);

        // Create an override.
        $override = new stdClass();
        $override->assignrecert = $assignrecertinstance->id;
        $override->groupid = 2;
        $override->id = $DB->insert_record('assignrecert_overrides', $override);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assignrecert->delete_override($override->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_assignrecert\event\group_override_deleted', $event);
        $this->assertEquals(context_module::instance($cm->id), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }
}
