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
 * Unit tests for (some of) mod/assignrecert/locallib.php.
 *
 * @package    mod_assignrecert
 * @category   phpunit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
require_once($CFG->dirroot . '/mod/assignrecert/tests/generator.php');

/**
 * Unit tests for (some of) mod/assignrecert/locallib.php.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assignrecert_locallib_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_assignrecert_test_generator;

    public function test_return_links() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();

        $assignrecert = $this->create_instance($course);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        $assignrecert->register_return_link('RETURNACTION', ['param' => 1]);
        $this->assertEquals('RETURNACTION', $assignrecert->get_return_action());
        $this->assertEquals(['param' => 1], $assignrecert->get_return_params());
    }

    public function test_get_feedback_plugins() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course);
        $installedplugins = array_keys(core_component::get_plugin_list('assignrecertfeedback'));

        foreach ($assignrecert->get_feedback_plugins() as $plugin) {
            $this->assertContains($plugin->get_type(), $installedplugins, 'Feedback plugin not in list of installed plugins');
        }
    }

    public function test_get_submission_plugins() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course);
        $installedplugins = array_keys(core_component::get_plugin_list('assignrecertsubmission'));

        foreach ($assignrecert->get_submission_plugins() as $plugin) {
            $this->assertContains($plugin->get_type(), $installedplugins, 'Submission plugin not in list of installed plugins');
        }
    }

    public function test_is_blind_marking() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, ['blindmarking' => 1]);
        $this->assertEquals(true, $assignrecert->is_blind_marking());

        // Test cannot see student names.
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertEquals(true, strpos($output, get_string('hiddenuser', 'assignrecert')));

        // Test students cannot reveal identities.
        $this->setUser($student);
        try {
            $assignrecert->reveal_identities();
            $this->fail('Expected exception was not thrown');
        } catch (required_capability_exception $e) {
            $this->assertStringContainsString(
                'Sorry, but you do not currently have permissions to do that (Reveal learner identities)',
                $e->getMessage()
            );
        }

        // Test teachers cannot reveal identities.
        $this->setUser($teacher);
        try {
            $assignrecert->reveal_identities();
            $this->fail('Expected exception was not thrown');
        } catch (required_capability_exception $e) {
            $this->assertStringContainsString(
                'Sorry, but you do not currently have permissions to do that (Reveal learner identities)',
                $e->getMessage()
            );
        }

        // Test editingteacher can reveal identities.
        $this->setUser($editingteacher);
        $assignrecert->reveal_identities();
        $this->assertEquals(false, $assignrecert->is_blind_marking());

        // Test student names are visible.
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertEquals(false, strpos($output, get_string('hiddenuser', 'assignrecert')));
    }

    /**
     * Data provider for test_get_assignrecert_perpage
     *
     * @return array Provider data
     */
    public function get_assignrecert_perpage_provider() {
        return array(
            array(
                'maxperpage' => -1,
                'userprefs' => array(
                    -1 => -1,
                    10 => 10,
                    20 => 20,
                    50 => 50,
                ),
            ),
            array(
                'maxperpage' => 15,
                'userprefs' => array(
                    -1 => 15,
                    10 => 10,
                    20 => 15,
                    50 => 15,
                ),
            ),
        );
    }

    /**
     * Test maxperpage
     *
     * @dataProvider get_assignrecert_perpage_provider
     * @param integer $maxperpage site config value
     * @param array $userprefs Array of user preferences and expected page sizes
     */
    public function test_get_assignrecert_perpage($maxperpage, $userprefs) {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course);

        set_config('maxperpage', $maxperpage, 'assignrecert');
        set_user_preference('assignrecert_perpage', null);
        $this->assertEquals(10, $assignrecert->get_assignrecert_perpage());
        foreach ($userprefs as $pref => $perpage) {
            set_user_preference('assignrecert_perpage', $pref);
            $this->assertEquals($perpage, $assignrecert->get_assignrecert_perpage());
        }
    }

    /**
     * Test submissions with extension date.
     */
    public function test_gradingtable_extension_due_date() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Setup the assignmentrecert.
        $this->setUser($teacher);
        $time = time();
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'duedate' => time() - (4 * DAYSECS),
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', array(
            'id' => $assignrecert->get_course_module()->id,
            'action' => 'grading',
        )));

        // Check that the assignmentrecert is late.
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_', 'assignrecert'), $output);
        $this->assertStringContainsString(get_string('overdue', 'assignrecert', format_time((4 * DAYSECS))), $output);

        // Grant an extension.
        $extendedtime = $time + (2 * DAYSECS);
        $assignrecert->testable_save_user_extension($student->id, $extendedtime);
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_', 'assignrecert'), $output);
        $this->assertStringContainsString(get_string('userextensiondate', 'assignrecert', userdate($extendedtime)), $output);

        // Simulate a submission.
        $this->setUser($student);
        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, false);
        $data = new stdClass();
        $data->onlinetext_editor = [
            'itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_MOODLE,
        ];
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Verify output.
        $this->setUser($teacher);
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_submitted', 'assignrecert'), $output);
        $this->assertStringContainsString(get_string('userextensiondate', 'assignrecert', userdate($extendedtime)), $output);
    }

    /**
     * Test that late submissions with extension date calculate correctly.
     */
    public function test_gradingtable_extension_date_calculation_for_lateness() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Setup the assignmentrecert.
        $this->setUser($teacher);
        $time = time();
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'duedate' => time() - (4 * DAYSECS),
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', array(
            'id' => $assignrecert->get_course_module()->id,
            'action' => 'grading',
        )));

        // Check that the assignmentrecert is late.
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_', 'assignrecert'), $output);
        $difftime = time() - $time;
        $this->assertStringContainsString(get_string('overdue', 'assignrecert', format_time((4 * DAYSECS) + $difftime)), $output);

        // Grant an extension that is in the past.
        $assignrecert->testable_save_user_extension($student->id, $time - (2 * DAYSECS));
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_', 'assignrecert'), $output);
        $this->assertStringContainsString(get_string('userextensiondate', 'assignrecert', userdate($time - (2 * DAYSECS))), $output);
        $difftime = time() - $time;
        $this->assertStringContainsString(get_string('overdue', 'assignrecert', format_time((2 * DAYSECS) + $difftime)), $output);

        // Simulate a submission.
        $this->setUser($student);
        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, false);
        $data = new stdClass();
        $data->onlinetext_editor = [
            'itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_MOODLE,
        ];
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);
        $submittedtime = time();

        // Verify output.
        $this->setUser($teacher);
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_submitted', 'assignrecert'), $output);
        $this->assertStringContainsString(get_string('userextensiondate', 'assignrecert', userdate($time - (2 * DAYSECS))), $output);

        $difftime = $submittedtime - $time;
        $this->assertStringContainsString(get_string('submittedlateshort', 'assignrecert', format_time((2 * DAYSECS) + $difftime)), $output);
    }

    public function test_gradingtable_status_rendering() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Setup the assignmentrecert.
        $this->setUser($teacher);
        $time = time();
        $assignrecert = $this->create_instance($course, [
            'assignrecertsubmission_onlinetext_enabled' => 1,
            'duedate' => $time - (4 * DAYSECS),
         ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', array(
            'id' => $assignrecert->get_course_module()->id,
            'action' => 'grading',
        )));

        // Check that the assignmentrecert is late.
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_', 'assignrecert'), $output);
        $difftime = time() - $time;
        $this->assertStringContainsString(get_string('overdue', 'assignrecert', format_time((4 * DAYSECS) + $difftime)), $output);

        // Simulate a student viewing the assignmentrecert without submitting.
        $this->setUser($student);
        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_NEW;
        $assignrecert->testable_update_submission($submission, $student->id, true, false);
        $submittedtime = time();

        // Verify output.
        $this->setUser($teacher);
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $difftime = $submittedtime - $time;
        $this->assertStringContainsString(get_string('overdue', 'assignrecert', format_time((4 * DAYSECS) + $difftime)), $output);

        $document = new DOMDocument();
        @$document->loadHTML($output);
        $xpath = new DOMXPath($document);
        $this->assertEquals('View submission', $xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r0_c8"])')); // Totara: extra flex icon text
    }

    /**
     * Check that group submission information is rendered correctly in the
     * grading table.
     */
    public function test_gradingtable_group_submissions_rendering() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        groups_add_member($group, $teacher);

        $students = [];

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $students[] = $student;
        groups_add_member($group, $student);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $students[] = $student;
        groups_add_member($group, $student);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $students[] = $student;
        groups_add_member($group, $student);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $students[] = $student;
        groups_add_member($group, $student);

        // Verify group assignmentrecerts.
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
            'teamsubmission' => 1,
            'assignrecertsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 1,
            'requireallteammemberssubmit' => 0,
        ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', array(
            'id' => $assignrecert->get_course_module()->id,
            'action' => 'grading',
        )));

        // Add a submission.
        $this->setUser($student);
        $data = new stdClass();
        $data->onlinetext_editor = [
            'itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_MOODLE,
        ];
        $notices = array();
        $assignrecert->save_submission($data, $notices);

        $submission = $assignrecert->get_group_submission($student->id, 0, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, true);

        // Check output.
        $this->setUser($teacher);
        $gradingtable = new assignrecert_grading_table($assignrecert, 4, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $document = new DOMDocument();
        @$document->loadHTML($output);
        $xpath = new DOMXPath($document);

        // Check status.
        $this->assertSame(get_string('submissionstatus_submitted', 'assignrecert'), $xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r0_c4"]/div[@class="submissionstatussubmitted"])'));
        $this->assertSame(get_string('submissionstatus_submitted', 'assignrecert'), $xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r3_c4"]/div[@class="submissionstatussubmitted"])'));

        // Check submission last modified date
        $this->assertGreaterThan(0, strtotime($xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r0_c8"])')));
        $this->assertGreaterThan(0, strtotime($xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r3_c8"])')));

        // Check group.
        $this->assertSame($group->name, $xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r0_c5"])'));
        $this->assertSame($group->name, $xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r3_c5"])'));

        // Check submission text.
        $this->assertSame('Submission text', $xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r0_c9"]/div/div)'));
        $this->assertSame('Submission text', $xpath->evaluate('string(//td[@id="mod_assignrecert_grading_r3_c9"]/div/div)'));

        // Check comments can be made.
        $this->assertSame(1, (int)$xpath->evaluate('count(//td[@id="mod_assignrecert_grading_r0_c10"]//textarea)'));
        $this->assertSame(1, (int)$xpath->evaluate('count(//td[@id="mod_assignrecert_grading_r3_c10"]//textarea)'));
    }

    public function test_show_intro() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        // Test whether we are showing the intro at the correct times.
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, ['alwaysshowdescription' => 1]);

        $this->assertEquals(true, $assignrecert->testable_show_intro());

        $tomorrow = time() + DAYSECS;

        $assignrecert = $this->create_instance($course, [
                'alwaysshowdescription' => 0,
                'allowsubmissionsfromdate' => $tomorrow,
            ]);
        $this->assertEquals(false, $assignrecert->testable_show_intro());
        $yesterday = time() - DAYSECS;
        $assignrecert = $this->create_instance($course, [
                'alwaysshowdescription' => 0,
                'allowsubmissionsfromdate' => $yesterday,
            ]);
        $this->assertEquals(true, $assignrecert->testable_show_intro());
    }

    public function test_has_submissions_or_grades() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, ['assignrecertsubmission_onlinetext_enabled' => 1]);
        $instance = $assignrecert->get_instance();

        // Should start empty.
        $this->assertEquals(false, $assignrecert->has_submissions_or_grades());

        // Simulate a submission.
        $this->setUser($student);
        $submission = $assignrecert->get_user_submission($student->id, true);

        // The submission is still new.
        $this->assertEquals(false, $assignrecert->has_submissions_or_grades());

        // Submit the submission.
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, false);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
                                         'text'=>'Submission text',
                                         'format'=>FORMAT_MOODLE);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Now test again.
        $this->assertEquals(true, $assignrecert->has_submissions_or_grades());
    }

    public function test_delete_grades() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course);

        // Simulate adding a grade.
        $this->setUser($teacher);
        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $student->id, 0);

        // Now see if the data is in the gradebook.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id);

        $this->assertNotEquals(0, count($gradinginfo->items));

        $assignrecert->testable_delete_grades();
        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id);

        $this->assertEquals(0, count($gradinginfo->items));
    }

    public function test_delete_instance() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, ['assignrecertsubmission_onlinetext_enabled' => 1]);

        // Simulate adding a grade.
        $this->setUser($teacher);
        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $student->id, 0);

        // Simulate a submission.
        $this->add_submission($student, $assignrecert);

        // Now try and delete.
        $this->setUser($teacher);
        $this->assertEquals(true, $assignrecert->delete_instance());
    }

    public function test_reset_userdata() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $now = time();
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'duedate' => $now,
            ]);

        // Simulate adding a grade.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        // Simulate a submission.
        $this->setUser($student);
        $submission = $assignrecert->get_user_submission($student->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
                                         'text'=>'Submission text',
                                         'format'=>FORMAT_MOODLE);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $this->assertEquals(true, $assignrecert->has_submissions_or_grades());
        // Now try and reset.
        $data = new stdClass();
        $data->reset_assignrecert_submissions = 1;
        $data->reset_gradebook_grades = 1;
        $data->reset_assignrecert_user_overrides = 1;
        $data->reset_assignrecert_group_overrides = 1;
        $data->courseid = $course->id;
        $data->timeshift = DAYSECS;
        $this->setUser($teacher);
        $assignrecert->reset_userdata($data);
        $this->assertEquals(false, $assignrecert->has_submissions_or_grades());

        // Reload the instance data.
        $instance = $DB->get_record('assignrecert', array('id'=>$assignrecert->get_instance()->id));
        $this->assertEquals($now + DAYSECS, $instance->duedate);

        // Test reset using assignrecert_reset_userdata().
        $assignduedate = $instance->duedate; // Keep old updated value for comparison.
        $data->timeshift = (2 * DAYSECS);
        assignrecert_reset_userdata($data);
        $instance = $DB->get_record('assignrecert', array('id' => $assignrecert->get_instance()->id));
        $this->assertEquals($assignduedate + (2 * DAYSECS), $instance->duedate);

        // Create one more assignmentrecert and reset, make sure time shifted for previous assignmentrecert is not changed.
        $assign2 = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'duedate' => $now,
            ]);
        $assignduedate = $instance->duedate;
        $data->timeshift = 3*DAYSECS;
        $assign2->reset_userdata($data);
        $instance = $DB->get_record('assignrecert', array('id' => $assignrecert->get_instance()->id));
        $this->assertEquals($assignduedate, $instance->duedate);
        $instance2 = $DB->get_record('assignrecert', array('id' => $assign2->get_instance()->id));
        $this->assertEquals($now + 3*DAYSECS, $instance2->duedate);

        // Reset both assignmentrecerts using assignrecert_reset_userdata() and make sure both assignmentrecerts have same date.
        $assignduedate = $instance->duedate;
        $assign2duedate = $instance2->duedate;
        $data->timeshift = (4 * DAYSECS);
        assignrecert_reset_userdata($data);
        $instance = $DB->get_record('assignrecert', array('id' => $assignrecert->get_instance()->id));
        $this->assertEquals($assignduedate + (4 * DAYSECS), $instance->duedate);
        $instance2 = $DB->get_record('assignrecert', array('id' => $assign2->get_instance()->id));
        $this->assertEquals($assign2duedate + (4 * DAYSECS), $instance2->duedate);
    }

    public function test_plugin_settings() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $now = time();
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_file_enabled' => 1,
                'assignrecertsubmission_file_maxfiles' => 12,
                'assignrecertsubmission_file_maxsizebytes' => 10,
            ]);

        $plugin = $assignrecert->get_submission_plugin_by_type('file');
        $this->assertEquals('12', $plugin->get_config('maxfilesubmissions'));
    }

    public function test_update_calendar() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $this->setUser($teacher);
        $userctx = context_user::instance($teacher->id)->id;

        // Hack to pretend that there was an editor involved. We need both $_POST and $_REQUEST, and a sesskey.
        $draftid = file_get_unused_draft_itemid();
        $_REQUEST['introeditor'] = $draftid;
        $_POST['introeditor'] = $draftid;
        $_POST['sesskey'] = sesskey();

        // Write links to a draft area.
        $fakearealink1 = file_rewrite_pluginfile_urls('<a href="@@PLUGINFILE@@/pic.gif">link</a>', 'draftfile.php', $userctx,
            'user', 'draft', $draftid);
        $fakearealink2 = file_rewrite_pluginfile_urls('<a href="@@PLUGINFILE@@/pic.gif">new</a>', 'draftfile.php', $userctx,
            'user', 'draft', $draftid);

        // Create a new assignmentrecert with links to a draft area.
        $now = time();
        $assignrecert = $this->create_instance($course, [
                'duedate' => $now,
                'intro' => $fakearealink1,
                'introformat' => FORMAT_HTML
            ]);

        // See if there is an event in the calendar.
        $params = array('modulename'=>'assignrecert', 'instance'=>$assignrecert->get_instance()->id);
        $event = $DB->get_record('event', $params);
        $this->assertNotEmpty($event);
        $this->assertSame('link', $event->description);     // The pluginfile links are removed.

        // Make sure the same works when updating the assignmentrecert.
        $instance = $assignrecert->get_instance();
        $instance->instance = $instance->id;
        $instance->intro = $fakearealink2;
        $instance->introformat = FORMAT_HTML;
        $assignrecert->update_instance($instance);
        $params = array('modulename' => 'assignrecert', 'instance' => $assignrecert->get_instance()->id);
        $event = $DB->get_record('event', $params);
        $this->assertNotEmpty($event);
        $this->assertSame('new', $event->description);     // The pluginfile links are removed.

        // Create an assignmentrecert with a description that should be hidden.
        $assignrecert = $this->create_instance($course, [
                'duedate' => $now + 160,
                'alwaysshowdescription' => false,
                'allowsubmissionsfromdate' => $now + 60,
                'intro' => 'Some text',
            ]);

        // Get the event from the calendar.
        $params = array('modulename'=>'assignrecert', 'instance'=>$assignrecert->get_instance()->id);
        $event = $DB->get_record('event', [
                'modulename' => 'assignrecert',
                'instance' => $assignrecert->get_instance()->id,
            ]);

        $this->assertEmpty($event->description);

        // Change the allowsubmissionfromdate to the past - do this directly in the DB
        // because if we call the assignmentrecert update method - it will update the calendar
        // and we want to test that this works from cron.
        $DB->set_field('assignrecert', 'allowsubmissionsfromdate', $now - 60, array('id'=>$assignrecert->get_instance()->id));
        // Run cron to update the event in the calendar.
        assignrecert::cron();
        $event = $DB->get_record('event', $params);

        $this->assertStringContainsString('Some text', $event->description);

    }

    public function test_update_instance() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, ['assignrecertsubmission_onlinetext_enabled' => 1]);

        $now = time();
        $instance = $assignrecert->get_instance();
        $instance->duedate = $now;
        $instance->instance = $instance->id;
        $instance->assignrecertsubmission_onlinetext_enabled = 1;

        $assignrecert->update_instance($instance);

        $instance = $DB->get_record('assignrecert', ['id' => $assignrecert->get_instance()->id]);
        $this->assertEquals($now, $instance->duedate);
    }

    public function test_cannot_submit_empty() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, ['submissiondrafts' => 1]);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Test you cannot see the submit button for an offline assignmentrecert regardless.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringNotContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output, 'Can submit empty offline assignmentrecert');
    }

    public function test_cannot_submit_empty_no_submission() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
            'submissiondrafts' => 1,
            'assignrecertsubmission_onlinetext_enabled' => 1,
        ]);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Test you cannot see the submit button for an online text assignmentrecert with no submission.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringNotContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output, 'Cannot submit empty onlinetext assignmentrecert');
    }

    public function test_can_submit_with_submission() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
            'submissiondrafts' => 1,
            'assignrecertsubmission_onlinetext_enabled' => 1,
        ]);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Add a draft.
        $this->add_submission($student, $assignrecert);

        // Test you can see the submit button for an online text assignmentrecert with a submission.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output, 'Can submit non empty onlinetext assignmentrecert');
    }

    /**
     * Test new_submission_empty
     *
     * We only test combinations of plugins here. Individual plugins are tested
     * in their respective test files.
     *
     * @dataProvider provider_new_submission_empty_testcases
     * @param string $data The file submission data
     * @param bool $expected The expected return value
     */
    public function test_new_submission_empty($data, $expected) {

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_file_enabled' => 1,
                'assignrecertsubmission_file_maxfiles' => 12,
                'assignrecertsubmission_file_maxsizebytes' => 10,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);
        $this->setUser($student);
        $submission = new stdClass();

        if ($data['file'] && isset($data['file']['filename'])) {
            $itemid = file_get_unused_draft_itemid();
            $submission->files_filemanager = $itemid;
            $data['file'] += ['contextid' => context_user::instance($student->id)->id, 'itemid' => $itemid];
            $fs = get_file_storage();
            $fs->create_file_from_string((object)$data['file'], 'Content of ' . $data['file']['filename']);
        }

        if ($data['onlinetext']) {
            $submission->onlinetext_editor = ['text' => $data['onlinetext']];
        }

        $result = $assignrecert->new_submission_empty($submission);
        $this->assertTrue($result === $expected);
    }

    /**
     * Dataprovider for the test_new_submission_empty testcase
     *
     * @return array of testcases
     */
    public function provider_new_submission_empty_testcases() {
        return [
            'With file and onlinetext' => [
                [
                    'file' => [
                        'component' => 'user',
                        'filearea' => 'draft',
                        'filepath' => '/',
                        'filename' => 'not_a_virus.exe'
                    ],
                    'onlinetext' => 'Balin Fundinul Uzbadkhazaddumu'
                ],
                false
            ]
        ];
    }

    public function test_list_participants() {
        global $CFG;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        // Create 10 students.
        for ($i = 0; $i < 10; $i++) {
            $this->getDataGenerator()->create_and_enrol($course, 'student');
        }

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, ['grade' => 100]);

        $this->assertCount(10, $assignrecert->list_participants(null, true));
    }

    public function test_list_participants_activeenrol() {
        global $CFG, $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        // Create 10 students.
        for ($i = 0; $i < 10; $i++) {
            $this->getDataGenerator()->create_and_enrol($course, 'student');
        }

        // Create 10 suspended students.
        for ($i = 0; $i < 10; $i++) {
            $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        }

        $this->setUser($teacher);
        set_user_preference('grade_report_showonlyactiveenrol', false);
        $assignrecert = $this->create_instance($course, ['grade' => 100]);

        $this->assertCount(10, $assignrecert->list_participants(null, true));
    }

    public function test_list_participants_with_group_restriction() {
        global $CFG;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $unrelatedstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Turn on availability and a group restriction, and check that it doesn't show users who aren't in the group.
        $CFG->enableavailability = true;

        $specialgroup = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $assignrecert = $this->create_instance($course, [
                'grade' => 100,
                'availability' => json_encode(
                    \core_availability\tree::get_root_json([\availability_group\condition::get_json($specialgroup->id)])
                ),
            ]);

        groups_add_member($specialgroup, $student);
        groups_add_member($specialgroup, $otherstudent);
        $this->assertEquals(2, count($assignrecert->list_participants(null, true)));
    }

    public function test_get_participant_user_not_exist() {
        $course = $this->getDataGenerator()->create_course();

        $assignrecert = $this->create_instance($course);
        $this->assertNull($assignrecert->get_participant('-1'));
    }

    public function test_get_participant_not_enrolled() {
        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->create_instance($course);

        $user = $this->getDataGenerator()->create_user();
        $this->assertNull($assignrecert->get_participant($user->id));
    }

    public function test_get_participant_no_submission() {
        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->create_instance($course);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $participant = $assignrecert->get_participant($student->id);

        $this->assertEquals($student->id, $participant->id);
        $this->assertFalse($participant->submitted);
        $this->assertFalse($participant->requiregrading);
        $this->assertFalse($participant->grantedextension);
    }

    public function test_get_participant_granted_extension() {
        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->create_instance($course);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert->save_user_extension($student->id, time());
        $participant = $assignrecert->get_participant($student->id);

        $this->assertEquals($student->id, $participant->id);
        $this->assertFalse($participant->submitted);
        $this->assertFalse($participant->requiregrading);
        $this->assertTrue($participant->grantedextension);
    }

    public function test_get_participant_with_ungraded_submission() {
        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->create_instance($course);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Simulate a submission.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        $participant = $assignrecert->get_participant($student->id);

        $this->assertEquals($student->id, $participant->id);
        $this->assertTrue($participant->submitted);
        $this->assertTrue($participant->requiregrading);
        $this->assertFalse($participant->grantedextension);
    }

    public function test_get_participant_with_graded_submission() {
        $course = $this->getDataGenerator()->create_course();
        $assignrecert = $this->create_instance($course);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Simulate a submission.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $student->id, 0);

        $participant = $assignrecert->get_participant($student->id);

        $this->assertEquals($student->id, $participant->id);
        $this->assertTrue($participant->submitted);
        $this->assertFalse($participant->requiregrading);
        $this->assertFalse($participant->grantedextension);
    }

    /**
     * No active group and non-group submissions disallowed => 2 groups.
     */
    public function test_count_teams_no_active_non_group_allowed() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group1, $student1);
        groups_add_member($group2, $student2);

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, ['teamsubmission' => 1]);

        $this->assertEquals(2, $assignrecert->count_teams());
    }

    /**
     * No active group and non group submissions allowed => 2 groups + the default one.
     */
    public function test_count_teams_non_group_allowed() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group1->id, 'groupingid' => $grouping->id));
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group2->id, 'groupingid' => $grouping->id));

        groups_add_member($group1, $student1);
        groups_add_member($group2, $student2);

        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
                'teamsubmissiongroupingid' => $grouping->id,
                'preventsubmissionnotingroup' => false,
            ]);

        $this->setUser($teacher);
        $this->assertEquals(3, $assignrecert->count_teams());

        // Active group only.
        $this->assertEquals(1, $assignrecert->count_teams($group1->id));
        $this->assertEquals(1, $assignrecert->count_teams($group2->id));
    }

    /**
     * Active group => just selected one.
     */
    public function test_count_teams_no_active_group() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group1->id, 'groupingid' => $grouping->id));
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group2->id, 'groupingid' => $grouping->id));

        groups_add_member($group1, $student1);
        groups_add_member($group2, $student2);

        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
                'preventsubmissionnotingroup' => true,
            ]);

        $this->setUser($teacher);
        $this->assertEquals(2, $assignrecert->count_teams());

        // Active group only.
        $this->assertEquals(1, $assignrecert->count_teams($group1->id));
        $this->assertEquals(1, $assignrecert->count_teams($group2->id));
    }

    /**
     * Active group => just selected one.
     */
    public function test_count_teams_groups_only() {

        $course = $this->getDataGenerator()->create_course();
        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));

        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
                'teamsubmissiongroupingid' => $grouping->id,
                'preventsubmissionnotingroup' => false,
            ]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group1, $student1);

        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group2, $student2);

        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group1->id, 'groupingid' => $grouping->id));
        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group2->id, 'groupingid' => $grouping->id));

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
                'preventsubmissionnotingroup' => true,
            ]);
        $this->assertEquals(2, $assignrecert->count_teams());
    }

    public function test_submit_to_default_group() {
        global $DB, $SESSION;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(['courseid' => $course->id]);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'submissiondrafts' => 0,
                'groupmode' => VISIBLEGROUPS,
            ]);

        $usergroup = $assignrecert->get_submission_group($student->id);
        $this->assertFalse($usergroup, 'New student is in default group');

        // Add a submission.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        // Set active groups to all groups.
        $this->setUser($teacher);
        $SESSION->activegroup[$course->id]['aag'][0] = 0;
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));

        // Set an active group.
        $SESSION->activegroup[$course->id]['aag'][0] = (int) $group->id;
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));
    }

    public function test_count_submissions_no_draft() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        $assignrecert->get_user_submission($student->id, true);

        // Note: Drafts count as a submission.
        $this->assertEquals(0, $assignrecert->count_grades());
        $this->assertEquals(0, $assignrecert->count_submissions());
        $this->assertEquals(1, $assignrecert->count_submissions(true));
        $this->assertEquals(0, $assignrecert->count_submissions_need_grading());
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_NEW));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_DRAFT));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_REOPENED));
    }

    public function test_count_submissions_draft() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        $this->add_submission($student, $assignrecert);

        // Note: Drafts count as a submission.
        $this->assertEquals(0, $assignrecert->count_grades());
        $this->assertEquals(1, $assignrecert->count_submissions());
        $this->assertEquals(1, $assignrecert->count_submissions(true));
        $this->assertEquals(0, $assignrecert->count_submissions_need_grading());
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_NEW));
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_DRAFT));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_REOPENED));
    }

    public function test_count_submissions_submitted() {
        global $SESSION;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        $this->assertEquals(0, $assignrecert->count_grades());
        $this->assertEquals(1, $assignrecert->count_submissions());
        $this->assertEquals(1, $assignrecert->count_submissions(true));
        $this->assertEquals(1, $assignrecert->count_submissions_need_grading());
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_NEW));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_DRAFT));
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_REOPENED));
    }

    public function test_count_submissions_graded() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        // Although it has been graded, it is still marked as submitted.
        $this->assertEquals(1, $assignrecert->count_grades());
        $this->assertEquals(1, $assignrecert->count_submissions());
        $this->assertEquals(1, $assignrecert->count_submissions(true));
        $this->assertEquals(0, $assignrecert->count_submissions_need_grading());
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_NEW));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_DRAFT));
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_REOPENED));
    }

    public function test_count_submissions_graded_group() {
        global $SESSION;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $othergroup = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $student);

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'groupmode' => VISIBLEGROUPS,
            ]);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        // The user should still be listed when fetching all groups.
        $this->setUser($teacher);
        $SESSION->activegroup[$course->id]['aag'][0] = 0;
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));

        // The user should still be listed when fetching just their group.
        $SESSION->activegroup[$course->id]['aag'][0] = $group->id;
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));

        // The user should still be listed when fetching just their group.
        $SESSION->activegroup[$course->id]['aag'][0] = $othergroup->id;
        $this->assertEquals(0, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));
    }

    // TODO
    public function x_test_count_submissions_for_team() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $othergroup = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $student);

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'teamsubmission' => 1,
            ]);

        // Add a graded submission.
        $this->add_submission($student, $assignrecert);



        // Simulate adding a grade.
        $this->setUser($teacher);
        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $this->extrastudents[0]->id, 0);

        // Simulate a submission.
        $this->setUser($this->extrastudents[1]);
        $submission = $assignrecert->get_group_submission($this->extrastudents[1]->id, $groupid, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $this->extrastudents[1]->id, true, false);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_MOODLE);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Simulate a submission.
        $this->setUser($this->extrastudents[2]);
        $submission = $assignrecert->get_group_submission($this->extrastudents[2]->id, $groupid, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $this->extrastudents[2]->id, true, false);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_MOODLE);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Simulate a submission.
        $this->setUser($this->extrastudents[3]);
        $submission = $assignrecert->get_group_submission($this->extrastudents[3]->id, $groupid, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $this->extrastudents[3]->id, true, false);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_MOODLE);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Simulate adding a grade.
        $this->setUser($teacher);
        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $this->extrastudents[3]->id, 0);
        $assignrecert->testable_apply_grade_to_user($data, $this->extrasuspendedstudents[0]->id, 0);

        // Create a new submission with status NEW.
        $this->setUser($this->extrastudents[4]);
        $submission = $assignrecert->get_group_submission($this->extrastudents[4]->id, $groupid, true);

        $this->assertEquals(2, $assignrecert->count_grades());
        $this->assertEquals(4, $assignrecert->count_submissions());
        $this->assertEquals(5, $assignrecert->count_submissions(true));
        $this->assertEquals(3, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED));
        $this->assertEquals(1, $assignrecert->count_submissions_with_status(ASSIGNRECERT_SUBMISSION_STATUS_DRAFT));
    }

    public function test_get_grading_userid_list_only_active() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $suspendedstudent = $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $this->setUser($teacher);

        $assignrecert = $this->create_instance($course);
        $this->assertCount(1, $assignrecert->testable_get_grading_userid_list());
    }

    public function test_get_grading_userid_list_all() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $suspendedstudent = $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $this->setUser($teacher);
        set_user_preference('grade_report_showonlyactiveenrol', false);

        $assignrecert = $this->create_instance($course);
        $this->assertCount(2, $assignrecert->testable_get_grading_userid_list());
    }

    public function test_cron() {

        // First run cron so there are no messages waiting to be sent (from other tests).
        cron_setup_user();
        assignrecert::cron();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now create an assignmentrecert and add some feedback.
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'sendstudentnotifications' => 1,
            ]);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        $this->expectOutputRegex('/Done processing 1 assignmentrecert submissions/');
        cron_setup_user();
        $sink = $this->redirectMessages();
        assignrecert::cron();
        $messages = $sink->get_messages();

        $this->assertEquals(1, count($messages));
        $this->assertEquals(1, $messages[0]->notification);
        $this->assertEquals($assignrecert->get_instance()->name, $messages[0]->contexturlname);
    }

    public function test_cron_without_notifications() {

        // First run cron so there are no messages waiting to be sent (from other tests).
        cron_setup_user();
        assignrecert::cron();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now create an assignmentrecert and add some feedback.
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'sendstudentnotifications' => 1,
            ]);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0, [
                'sendstudentnotifications' => 0,
            ]);

        cron_setup_user();
        $sink = $this->redirectMessages();
        assignrecert::cron();
        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages));
    }

    public function test_cron_regraded() {

        // First run cron so there are no messages waiting to be sent (from other tests).
        cron_setup_user();
        assignrecert::cron();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now create an assignmentrecert and add some feedback.
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'sendstudentnotifications' => 1,
            ]);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        $this->expectOutputRegex('/Done processing 1 assignmentrecert submissions/');
        cron_setup_user();
        assignrecert::cron();

        // Regrade.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        $this->expectOutputRegex('/Done processing 1 assignmentrecert submissions/');
        cron_setup_user();
        $sink = $this->redirectMessages();
        assignrecert::cron();
        $messages = $sink->get_messages();

        $this->assertEquals(1, count($messages));
        $this->assertEquals(1, $messages[0]->notification);
        $this->assertEquals($assignrecert->get_instance()->name, $messages[0]->contexturlname);
    }

    /**
     * Test delivery of grade notifications as controlled by marking workflow.
     */
    public function test_markingworkflow_cron() {

        // First run cron so there are no messages waiting to be sent (from other tests).
        cron_setup_user();
        assignrecert::cron();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now create an assignmentrecert and add some feedback.
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'sendstudentnotifications' => 1,
                'markingworkflow' => 1,
            ]);

        // Mark a submission but set the workflowstate to an unreleased state.
        // This should not trigger a notification.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0, [
                'sendstudentnotifications' => 1,
                'workflowstate' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_READYFORRELEASE,
            ]);

        cron_setup_user();
        $sink = $this->redirectMessages();
        assignrecert::cron();
        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages));

        // Transition to the released state.
        $this->setUser($teacher);
        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->workflowstate = ASSIGNRECERT_MARKING_WORKFLOW_STATE_RELEASED;
        $assignrecert->testable_apply_grade_to_user($submission, $student->id, 0);

        // Now run cron and see that one message was sent.
        cron_setup_user();
        $sink = $this->redirectMessages();
        $this->expectOutputRegex('/Done processing 1 assignmentrecert submissions/');
        assignrecert::cron();
        $messages = $sink->get_messages();

        $this->assertEquals(1, count($messages));
        $this->assertEquals(1, $messages[0]->notification);
        $this->assertEquals($assignrecert->get_instance()->name, $messages[0]->contexturlname);
    }

    public function test_cron_message_includes_courseid() {
        // First run cron so there are no messages waiting to be sent (from other tests).
        cron_setup_user();
        assignrecert::cron();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now create an assignmentrecert and add some feedback.
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'sendstudentnotifications' => 1,
            ]);

        // Mark a submission but set the workflowstate to an unreleased state.
        // This should not trigger a notification.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student);

        // Now run cron and see that one message was sent.
        cron_setup_user();
        $sink = $this->redirectEvents();
        $this->expectOutputRegex('/Done processing 1 assignmentrecert submissions/');
        assignrecert::cron();

        $events = $sink->get_events();

        // Two notifications are sent, one to student and one to teacher. This generates
        // four events:
        // core\event\notification_sent
        // core\event\notification_viewed
        // core\event\notification_sent
        // core\event\notification_viewed.
        $event = reset($events);

        $this->assertInstanceOf('\core\event\notification_sent', $event);
        $this->assertEquals($assignrecert->get_course()->id, $event->other['courseid']);
        $sink->close();
    }

    public function test_is_graded() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course);

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        $this->setUser($teacher);
        $this->assertEquals(true, $assignrecert->testable_is_graded($student->id));
        $this->assertEquals(false, $assignrecert->testable_is_graded($otherstudent->id));
    }

    public function test_can_grade() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course);

        $this->setUser($student);
        $this->assertEquals(false, $assignrecert->can_grade());

        $this->setUser($teacher);
        $this->assertEquals(true, $assignrecert->can_grade());

        // Test the viewgrades capability - without mod/assignrecert:grade.
        $this->setUser($student);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        assignrecert_capability('mod/assignrecert:viewgrades', CAP_ALLOW, $studentrole->id, $assignrecert->get_context()->id);
        $this->assertEquals(false, $assignrecert->can_grade());
    }

    public function test_can_view_submission() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $suspendedstudent = $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $assignrecert = $this->create_instance($course);

        $this->setUser($student);
        $this->assertEquals(true, $assignrecert->can_view_submission($student->id));
        $this->assertEquals(false, $assignrecert->can_view_submission($otherstudent->id));
        $this->assertEquals(false, $assignrecert->can_view_submission($teacher->id));

        $this->setUser($teacher);
        $this->assertEquals(true, $assignrecert->can_view_submission($student->id));
        $this->assertEquals(true, $assignrecert->can_view_submission($otherstudent->id));
        $this->assertEquals(true, $assignrecert->can_view_submission($teacher->id));
        $this->assertEquals(false, $assignrecert->can_view_submission($suspendedstudent->id));

        $this->setUser($editingteacher);
        $this->assertEquals(true, $assignrecert->can_view_submission($student->id));
        $this->assertEquals(true, $assignrecert->can_view_submission($otherstudent->id));
        $this->assertEquals(true, $assignrecert->can_view_submission($teacher->id));
        $this->assertEquals(true, $assignrecert->can_view_submission($suspendedstudent->id));

        // Test the viewgrades capability - without mod/assignrecert:grade.
        $this->setUser($student);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        assignrecert_capability('mod/assignrecert:viewgrades', CAP_ALLOW, $studentrole->id, $assignrecert->get_context()->id);
        $this->assertEquals(true, $assignrecert->can_view_submission($student->id));
        $this->assertEquals(true, $assignrecert->can_view_submission($otherstudent->id));
        $this->assertEquals(true, $assignrecert->can_view_submission($teacher->id));
        $this->assertEquals(false, $assignrecert->can_view_submission($suspendedstudent->id));
    }

    public function test_update_submission() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course);

        $this->add_submission($student, $assignrecert);
        $submission = $assignrecert->get_user_submission($student->id, 0);
        $assignrecert->testable_update_submission($submission, $student->id, true, true);

        $this->setUser($teacher);

        // Verify the gradebook update.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id, $student->id);

        $this->assertTrue(isset($gradinginfo->items[0]->grades[$student->id]));
        $this->assertEquals($student->id, $gradinginfo->items[0]->grades[$student->id]->usermodified);
    }

    public function test_update_submission_team() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group, $student);

        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group, $otherstudent);

        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
            ]);

        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id, $student->id);
        $this->assertTrue(isset($gradinginfo->items[0]->grades[$student->id]));
        $this->assertNull($gradinginfo->items[0]->grades[$student->id]->usermodified);

        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id, $otherstudent->id);
        $this->asserttrue(isset($gradinginfo->items[0]->grades[$otherstudent->id]));
        $this->assertNull($gradinginfo->items[0]->grades[$otherstudent->id]->usermodified);

        $this->add_submission($student, $assignrecert);
        $submission = $assignrecert->get_group_submission($student->id, 0, true);
        $assignrecert->testable_update_submission($submission, $student->id, true, true);

        // Verify the gradebook update for the student.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id, $student->id);

        $this->assertTrue(isset($gradinginfo->items[0]->grades[$student->id]));
        $this->assertEquals($student->id, $gradinginfo->items[0]->grades[$student->id]->usermodified);

        // Verify the gradebook update for the other student.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id, $otherstudent->id);

        $this->assertTrue(isset($gradinginfo->items[0]->grades[$otherstudent->id]));
        $this->assertEquals($otherstudent->id, $gradinginfo->items[0]->grades[$otherstudent->id]->usermodified);
    }

    public function test_update_submission_suspended() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $assignrecert = $this->create_instance($course);

        $this->add_submission($student, $assignrecert);
        $submission = $assignrecert->get_user_submission($student->id, 0);
        $assignrecert->testable_update_submission($submission, $student->id, true, false);

        $this->setUser($teacher);

        // Verify the gradebook update.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id, $student->id);

        $this->assertTrue(isset($gradinginfo->items[0]->grades[$student->id]));
        $this->assertEquals($student->id, $gradinginfo->items[0]->grades[$student->id]->usermodified);
    }

    public function test_update_submission_blind() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'blindmarking' => 1,
            ]);

        $this->add_submission($student, $assignrecert);
        $submission = $assignrecert->get_user_submission($student->id, 0);
        $assignrecert->testable_update_submission($submission, $student->id, true, false);

        // Verify the gradebook update.
        $gradinginfo = grade_get_grades($course->id, 'mod', 'assignrecert', $assignrecert->get_instance()->id, $student->id);

        // The usermodified is not set because this is blind marked.
        $this->assertTrue(isset($gradinginfo->items[0]->grades[$student->id]));
        $this->assertNull($gradinginfo->items[0]->grades[$student->id]->usermodified);
    }

    public function test_group_submissions_submit_for_marking_requireallteammemberssubmit() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group, $student);

        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group, $otherstudent);

        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'submissiondrafts' => 1,
                'requireallteammemberssubmit' => 1,
            ]);

        // Now verify group assignmentrecerts.
        $this->setUser($teacher);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Add a submission.
        $this->add_submission($student, $assignrecert);

        // Check we can see the submit button.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);

        $submission = $assignrecert->get_group_submission($student->id, 0, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, true);

        // Check that the student does not see "Submit" button.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringNotContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);

        // Change to another user in the same group.
        $this->setUser($otherstudent);
        $output = $assignrecert->view_student_summary($otherstudent, true);
        $this->assertStringContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);

        $submission = $assignrecert->get_group_submission($otherstudent->id, 0, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $otherstudent->id, true, true);
        $output = $assignrecert->view_student_summary($otherstudent, true);
        $this->assertStringNotContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);
    }

    public function test_group_submissions_submit_for_marking() {
        global $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group, $student);

        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group, $otherstudent);

        // Now verify group assignmentrecerts.
        $this->setUser($teacher);
        $time = time();
        $assignrecert = $this->create_instance($course, [
                'teamsubmission' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'submissiondrafts' => 1,
                'requireallteammemberssubmit' => 0,
                'duedate' => $time - (2 * DAYSECS),
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Add a submission.
        $this->add_submission($student, $assignrecert);


        // Check we can see the submit button.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);
        $this->assertStringContainsString(get_string('timeremaining', 'assignrecert'), $output);
        $difftime = time() - $time;
        $this->assertStringContainsString(get_string('overdue', 'assignrecert', format_time((2 * DAYSECS) + $difftime)), $output);

        $submission = $assignrecert->get_group_submission($student->id, 0, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, true);

        // Check that the student does not see "Submit" button.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringNotContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);

        // Change to another user in the same group.
        $this->setUser($otherstudent);
        $output = $assignrecert->view_student_summary($otherstudent, true);
        $this->assertStringNotContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);

        // Check that time remaining is not overdue.
        $this->assertStringContainsString(get_string('timeremaining', 'assignrecert'), $output);
        $difftime = time() - $time;
        $this->assertStringContainsString(get_string('submittedlate', 'assignrecert', format_time((2 * DAYSECS) + $difftime)), $output);

        $submission = $assignrecert->get_group_submission($otherstudent->id, 0, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $otherstudent->id, true, true);
        $output = $assignrecert->view_student_summary($otherstudent, true);
        $this->assertStringNotContainsString(get_string('submitassignmentrecert', 'assignrecert'), $output);
    }

    public function test_submissions_open() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $suspendedstudent = $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $this->setAdminUser();

        $now = time();
        $tomorrow = $now + DAYSECS;
        $oneweek = $now + WEEKSECS;
        $yesterday = $now - DAYSECS;

        $assignrecert = $this->create_instance($course);
        $this->assertEquals(true, $assignrecert->testable_submissions_open($student->id));

        $assignrecert = $this->create_instance($course, ['duedate' => $tomorrow]);
        $this->assertEquals(true, $assignrecert->testable_submissions_open($student->id));

        $assignrecert = $this->create_instance($course, ['duedate' => $yesterday]);
        $this->assertEquals(true, $assignrecert->testable_submissions_open($student->id));

        $assignrecert = $this->create_instance($course, ['duedate' => $yesterday, 'cutoffdate' => $tomorrow]);
        $this->assertEquals(true, $assignrecert->testable_submissions_open($student->id));

        $assignrecert = $this->create_instance($course, ['duedate' => $yesterday, 'cutoffdate' => $yesterday]);
        $this->assertEquals(false, $assignrecert->testable_submissions_open($student->id));

        $assignrecert->testable_save_user_extension($student->id, $tomorrow);
        $this->assertEquals(true, $assignrecert->testable_submissions_open($student->id));

        $assignrecert = $this->create_instance($course, ['submissiondrafts' => 1]);
        $this->assertEquals(true, $assignrecert->testable_submissions_open($student->id));

        $this->setUser($student);
        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $student->id, true, false);

        $this->setUser($teacher);
        $this->assertEquals(false, $assignrecert->testable_submissions_open($student->id));
    }

    public function test_get_graders() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        // Create an assignmentrecert with no groups.
        $assignrecert = $this->create_instance($course);
        $this->assertCount(2, $assignrecert->testable_get_graders($student->id));
    }

    public function test_get_graders_separate_groups() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group1, $student);

        $this->setAdminUser();

        // Force create an assignmentrecert with SEPARATEGROUPS.
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));

        $assignrecert = $this->create_instance($course, [
                'groupingid' => $grouping->id,
                'groupmode' => SEPARATEGROUPS,
            ]);

        $this->assertCount(4, $assignrecert->testable_get_graders($student->id));

        // Note the second student is in a group that is not in the grouping.
        // This means that we get all graders that are not in a group in the grouping.
        $this->assertCount(4, $assignrecert->testable_get_graders($otherstudent->id));
    }

    public function test_get_notified_users() {
        global $CFG, $DB;


        $course = $this->getDataGenerator()->create_course();
        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group1->id, 'groupingid' => $grouping->id));

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        groups_add_member($group1, $teacher);

        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        groups_add_member($group1, $editingteacher);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group1, $student);

        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $capability = 'mod/assignrecert:receivegradernotifications';
        $coursecontext = context_course::instance($course->id);
        $role = $DB->get_record('role', array('shortname' => 'teacher'));

        $this->setUser($teacher);

        // Create an assignmentrecert with no groups.
        $assignrecert = $this->create_instance($course);

        $this->assertCount(3, $assignrecert->testable_get_notifiable_users($student->id));

        // Change nonediting teachers role to not receive grader notifications.
        assignrecert_capability($capability, CAP_PROHIBIT, $role->id, $coursecontext);

        // Only the editing teachers will be returned.
        $this->assertCount(1, $assignrecert->testable_get_notifiable_users($student->id));

        // Note the second student is in a group that is not in the grouping.
        // This means that we get all graders that are not in a group in the grouping.
        $this->assertCount(1, $assignrecert->testable_get_notifiable_users($otherstudent->id));
    }

    public function test_get_notified_users_in_grouping() {
        global $CFG, $DB;


        $course = $this->getDataGenerator()->create_course();
        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_grouping_group(array('groupid' => $group1->id, 'groupingid' => $grouping->id));

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        groups_add_member($group1, $teacher);

        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        groups_add_member($group1, $editingteacher);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group1, $student);

        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        // Force create an assignmentrecert with SEPARATEGROUPS.
        $assignrecert = $this->create_instance($course, [
                'groupingid' => $grouping->id,
                'groupmode' => SEPARATEGROUPS,
            ]);

        // Student is in a group - only the tacher and editing teacher in the group shoudl be present.
        $this->setUser($student);
        $this->assertCount(2, $assignrecert->testable_get_notifiable_users($student->id));

        // Note the second student is in a group that is not in the grouping.
        // This means that we get all graders that are not in a group in the grouping.
        $this->assertCount(1, $assignrecert->testable_get_notifiable_users($otherstudent->id));

        // Change nonediting teachers role to not receive grader notifications.
        $capability = 'mod/assignrecert:receivegradernotifications';
        $coursecontext = context_course::instance($course->id);
        $role = $DB->get_record('role', ['shortname' => 'teacher']);
        assignrecert_capability($capability, CAP_PROHIBIT, $role->id, $coursecontext);

        // Only the editing teachers will be returned.
        $this->assertCount(1, $assignrecert->testable_get_notifiable_users($student->id));

        // Note the second student is in a group that is not in the grouping.
        // This means that we get all graders that are not in a group in the grouping.
        // Unfortunately there are no editing teachers who are not in a group.
        $this->assertCount(0, $assignrecert->testable_get_notifiable_users($otherstudent->id));
    }

    public function test_group_members_only() {
        global $CFG;


        $course = $this->getDataGenerator()->create_course();
        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_grouping_group([
                'groupid' => $group1->id,
                'groupingid' => $grouping->id,
            ]);

        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_grouping_group([
                'groupid' => $group2->id,
                'groupingid' => $grouping->id,
            ]);

        $group3 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        // Add users in the following groups
        // - Teacher - Group 1.
        // - Student - Group 1.
        // - Student - Group 2.
        // - Student - Unrelated Group
        // - Student - No group.
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        groups_add_member($group1, $teacher);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group1, $student);

        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group2, $otherstudent);

        $yetotherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group2, $otherstudent);

        $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $CFG->enableavailability = true;
        $assignrecert = $this->create_instance($course, [], [
                'availability' => json_encode(
                    \core_availability\tree::get_root_json([\availability_grouping\condition::get_json()])
                ),
                'groupingid' => $grouping->id,
            ]);

        // The two students in groups should be returned, but not the teacher in the group, or the student not in the
        // group, or the student in an unrelated group.
        $this->setUser($teacher);
        $participants = $assignrecert->list_participants(0, true);
        $this->assertCount(2, $participants);
        $this->assertTrue(isset($participants[$student->id]));
        $this->assertTrue(isset($participants[$otherstudent->id]));
    }

    public function test_get_uniqueid_for_user() {

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $students = [];
        for ($i = 0; $i < 10; $i++) {
            $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $students[$student->id] = $student;
        }

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course);

        foreach ($students as $student) {
            $uniqueid = $assignrecert->get_uniqueid_for_user($student->id);
            $this->assertEquals($student->id, $assignrecert->get_user_id_for_uniqueid($uniqueid));
        }
    }

    public function test_show_student_summary() {
        global $CFG, $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // No feedback should be available because this student has not been graded.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertDoesNotMatchRegularExpression('/Feedback/', $output, 'Do not show feedback if there is no grade');

        // Simulate adding a grade.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student);

        // Now we should see the feedback.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertMatchesRegularExpression('/Feedback/', $output, 'Show feedback if there is a grade');

        // Now hide the grade in gradebook.
        $this->setUser($teacher);
        require_once($CFG->libdir.'/gradelib.php');
        $gradeitem = new grade_item(array(
            'itemtype'      => 'mod',
            'itemmodule'    => 'assignrecert',
            'iteminstance'  => $assignrecert->get_instance()->id,
            'courseid'      => $course->id));

        $gradeitem->set_hidden(1, false);

        // No feedback should be available because the grade is hidden.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertDoesNotMatchRegularExpression('/Feedback/', $output, 'Do not show feedback if the grade is hidden in the gradebook');
    }

    public function test_show_student_summary_with_feedback() {
        global $CFG, $PAGE;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'assignrecertfeedback_comments_enabled' => 1
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // No feedback should be available because this student has not been graded.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertDoesNotMatchRegularExpression('/Feedback/', $output);

        // Simulate adding a grade.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);
        $this->mark_submission($teacher, $assignrecert, $student, null, [
                'assignrecertfeedbackcomments_editor' => [
                    'text' => 'Tomato sauce',
                    'format' => FORMAT_MOODLE,
                ],
            ]);

        // Should have feedback but no grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertMatchesRegularExpression('/Feedback/', $output);
        $this->assertMatchesRegularExpression('/Tomato sauce/', $output);
        $this->assertDoesNotMatchRegularExpression('/Grade/', $output, 'Do not show grade when there is no grade.');
        $this->assertDoesNotMatchRegularExpression('/Graded on/', $output, 'Do not show graded date when there is no grade.');

        // Add a grade now.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0, [
                'assignrecertfeedbackcomments_editor' => [
                    'text' => 'Bechamel sauce',
                    'format' => FORMAT_MOODLE,
                ],
            ]);

        // Should have feedback but no grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertDoesNotMatchRegularExpression('/Tomato sauce/', $output);
        $this->assertMatchesRegularExpression('/Bechamel sauce/', $output);
        $this->assertMatchesRegularExpression('/Grade/', $output);
        $this->assertMatchesRegularExpression('/Graded on/', $output);

        // Now hide the grade in gradebook.
        $this->setUser($teacher);
        $gradeitem = new grade_item(array(
            'itemtype'      => 'mod',
            'itemmodule'    => 'assignrecert',
            'iteminstance'  => $assignrecert->get_instance()->id,
            'courseid'      => $course->id));

        $gradeitem->set_hidden(1, false);

        // No feedback should be available because the grade is hidden.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertDoesNotMatchRegularExpression('/Feedback/', $output, 'Do not show feedback if the grade is hidden in the gradebook');
    }

    /**
     * Test reopen behavior when in "Manual" mode.
     */
    public function test_attempt_reopen_method_manual() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $assignrecert = $this->create_instance($course, [
                'attemptreopenmethod' => ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts' => 3,
                'submissiondrafts' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Student should be able to see an add submission button.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assignrecert')));

        // Add a submission.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        // Verify the student cannot make changes to the submission.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, get_string('addsubmission', 'assignrecert')));

        // Mark the submission.
        $this->mark_submission($teacher, $assignrecert, $student);

        // Check the student can see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, '50.0'));

        // Allow the student another attempt.
        $teacher->ignoresesskey = true;
        $this->setUser($teacher);
        $result = $assignrecert->testable_process_add_attempt($student->id);
        $this->assertEquals(true, $result);

        // Check that the previous attempt is now in the submission history table.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        // Need a better check.
        $this->assertNotEquals(false, strpos($output, 'Submission text'), 'Contains: Submission text');

        // Check that the student now has a button for Add a new attempt".
        $this->assertNotEquals(false, strpos($output, get_string('addnewattempt', 'assignrecert')));
        // Check that the student now does not have a button for Submit.
        $this->assertEquals(false, strpos($output, get_string('submitassignmentrecert', 'assignrecert')));

        // Check that the student now has a submission history.
        $this->assertNotEquals(false, strpos($output, get_string('attempthistory', 'assignrecert')));

        $this->setUser($teacher);
        // Check that the grading table loads correctly and contains this user.
        // This is also testing that we do not get duplicate rows in the grading table.
        $gradingtable = new assignrecert_grading_table($assignrecert, 100, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertEquals(true, strpos($output, $student->lastname));

        // Should be 1 not 2.
        $this->assertEquals(1, $assignrecert->count_submissions());
        $this->assertEquals(1, $assignrecert->count_submissions_with_status('reopened'));
        $this->assertEquals(0, $assignrecert->count_submissions_need_grading());
        $this->assertEquals(1, $assignrecert->count_grades());

        // Change max attempts to unlimited.
        $formdata = clone($assignrecert->get_instance());
        $formdata->maxattempts = ASSIGNRECERT_UNLIMITED_ATTEMPTS;
        $formdata->instance = $formdata->id;
        $assignrecert->update_instance($formdata);

        // Mark the submission again.
        $this->mark_submission($teacher, $assignrecert, $student, 60.0, [], 1);

        // Check the grade exists.
        $this->setUser($teacher);
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEquals(60, (int) $grades[$student->id]->rawgrade);

        // Check we can reopen still.
        $result = $assignrecert->testable_process_add_attempt($student->id);
        $this->assertEquals(true, $result);

        // Should no longer have a grade because there is no grade for the latest attempt.
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEmpty($grades);
    }

    /**
     * Test reopen behavior when in "Reopen until pass" mode.
     */
    public function test_attempt_reopen_method_untilpass() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $assignrecert = $this->create_instance($course, [
                'attemptreopenmethod' => ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_UNTILPASS,
                'maxattempts' => 3,
                'submissiondrafts' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Set grade to pass to 80.
        $gradeitem = $assignrecert->get_grade_item();
        $gradeitem->gradepass = '80.0';
        $gradeitem->update();

        // Student should be able to see an add submission button.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assignrecert')));

        // Add a submission.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        // Verify the student cannot make a new attempt.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, get_string('addnewattempt', 'assignrecert')));

        // Mark the submission as non-passing.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0);

        // Check the student can see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, '50.0'));

        // Check that the student now has a button for Add a new attempt.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, get_string('addnewattempt', 'assignrecert')));

        // Check that the student now does not have a button for Submit.
        $this->assertEquals(false, strpos($output, get_string('submitassignmentrecert', 'assignrecert')));

        // Check that the student now has a submission history.
        $this->assertNotEquals(false, strpos($output, get_string('attempthistory', 'assignrecert')));

        // Add a second submission.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        // Mark the submission as passing.
        $this->mark_submission($teacher, $assignrecert, $student, 80.0);

        // Check that the student does not have a button for Add a new attempt.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, get_string('addnewattempt', 'assignrecert')));

        // Re-mark the submission as not passing.
        $this->mark_submission($teacher, $assignrecert, $student, 40.0, [], 1);

        // Check that the student now has a button for Add a new attempt.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertMatchesRegularExpression('/' . get_string('addnewattempt', 'assignrecert') . '/', $output);
        $this->assertNotEquals(false, strpos($output, get_string('addnewattempt', 'assignrecert')));
    }

    public function test_attempt_reopen_method_untilpass_passing() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $assignrecert = $this->create_instance($course, [
                'attemptreopenmethod' => ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_UNTILPASS,
                'maxattempts' => 3,
                'submissiondrafts' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Set grade to pass to 80.
        $gradeitem = $assignrecert->get_grade_item();
        $gradeitem->gradepass = '80.0';
        $gradeitem->update();

        // Student should be able to see an add submission button.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assignrecert')));

        // Add a submission as a student.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        // Mark the submission as passing.
        $this->mark_submission($teacher, $assignrecert, $student, 100.0);

        // Check the student can see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, '100.0'));

        // Check that the student does not have a button for Add a new attempt.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, get_string('addnewattempt', 'assignrecert')));
    }

    public function test_attempt_reopen_method_untilpass_no_passing_requirement() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $assignrecert = $this->create_instance($course, [
                'attemptreopenmethod' => ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_UNTILPASS,
                'maxattempts' => 3,
                'submissiondrafts' => 1,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);
        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Set grade to pass to 0, so that no attempts should reopen.
        $gradeitem = $assignrecert->get_grade_item();
        $gradeitem->gradepass = '0';
        $gradeitem->update();

        // Student should be able to see an add submission button.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assignrecert')));

        // Add a submission.
        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert);

        // Mark the submission with any grade.
        $this->mark_submission($teacher, $assignrecert, $student, 0.0);

        // Check the student can see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, '0.0'));

        // Check that the student does not have a button for Add a new attempt.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, get_string('addnewattempt', 'assignrecert')));
    }

    /**
     * Test student visibility for each stage of the marking workflow.
     */
    public function test_markingworkflow() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $assignrecert = $this->create_instance($course, [
                'markingworkflow' => 1,
            ]);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Mark the submission and set to notmarked.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0,  [
                'workflowstate' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_NOTMARKED,
            ]);

        // Check the student can't see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, '50.0'));

        // Make sure the grade isn't pushed to the gradebook.
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEmpty($grades);

        // Mark the submission and set to inmarking.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0,  [
                'workflowstate' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_INMARKING,
            ]);

        // Check the student can't see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, '50.0'));

        // Make sure the grade isn't pushed to the gradebook.
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEmpty($grades);

        // Mark the submission and set to readyforreview.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0,  [
                'workflowstate' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_READYFORREVIEW,
            ]);

        // Check the student can't see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, '50.0'));

        // Make sure the grade isn't pushed to the gradebook.
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEmpty($grades);

        // Mark the submission and set to inreview.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0,  [
                'workflowstate' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_INREVIEW,
            ]);

        // Check the student can't see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, '50.0'));

        // Make sure the grade isn't pushed to the gradebook.
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEmpty($grades);

        // Mark the submission and set to readyforrelease.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0,  [
                'workflowstate' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_READYFORRELEASE,
            ]);

        // Check the student can't see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertEquals(false, strpos($output, '50.0'));

        // Make sure the grade isn't pushed to the gradebook.
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEmpty($grades);

        // Mark the submission and set to released.
        $this->mark_submission($teacher, $assignrecert, $student, 50.0,  [
                'workflowstate' => ASSIGNRECERT_MARKING_WORKFLOW_STATE_RELEASED,
            ]);

        // Check the student can see the grade.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, '50.0'));

        // Make sure the grade is pushed to the gradebook.
        $grades = $assignrecert->get_user_grades_for_gradebook($student->id);
        $this->assertEquals(50, (int)$grades[$student->id]->rawgrade);
    }

    /**
     * Test that a student allocated a specific marker is only shown to that marker.
     */
    public function test_markerallocation() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $otherteacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $assignrecert = $this->create_instance($course, [
                'markingworkflow' => 1,
                'markingallocation' => 1
            ]);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Allocate marker to submission.
        $this->mark_submission($teacher, $assignrecert, $student, null, [
            'allocatedmarker' => $teacher->id,
        ]);

        // Check the allocated marker can view the submission.
        $this->setUser($teacher);
        $users = $assignrecert->list_participants(0, true);
        $this->assertEquals(1, count($users));
        $this->assertTrue(isset($users[$student->id]));

        $cm = get_coursemodule_from_instance('assignrecert', $assignrecert->get_instance()->id);
        $context = context_module::instance($cm->id);
        $assignrecert = new mod_assignrecert_testable_assignrecert($context, $cm, $course);

        // Check that other teachers can't view this submission.
        $this->setUser($otherteacher);
        $users = $assignrecert->list_participants(0, true);
        $this->assertEquals(0, count($users));
    }

    /**
     * Ensure that a teacher cannot submit for students as standard.
     */
    public function test_teacher_submit_for_student() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $assignrecert = $this->create_instance($course, [
            'assignrecertsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 1,
        ]);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Add a submission but do not submit.
        $this->add_submission($student, $assignrecert, 'Student submission text');

        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringContainsString('Student submission text', $output, 'Contains student submission text');

        // Check that a teacher can not edit the submission as they do not have the capability.
        $this->setUser($teacher);
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('error/nopermission');
        $this->add_submission($student, $assignrecert, 'Teacher edited submission text', false);
    }

    /**
     * Ensure that a teacher with the editothersubmission capability can submit on behalf of a student.
     */
    public function test_teacher_submit_for_student_with_capability() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $otherteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $assignrecert = $this->create_instance($course, [
            'assignrecertsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 1,
        ]);

        // Add the required capability.
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        assignrecert_capability('mod/assignrecert:editothersubmission', CAP_ALLOW, $roleid, $assignrecert->get_context()->id);
        role_assignrecert($roleid, $teacher->id, $assignrecert->get_context()->id);
        accesslib_clear_all_caches_for_unit_testing();

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Add a submission but do not submit.
        $this->add_submission($student, $assignrecert, 'Student submission text');

        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringContainsString('Student submission text', $output, 'Contains student submission text');

        // Check that a teacher can edit the submission.
        $this->setUser($teacher);
        $this->add_submission($student, $assignrecert, 'Teacher edited submission text', false);

        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringNotContainsString('Student submission text', $output, 'Contains student submission text');
        $this->assertStringContainsString('Teacher edited submission text', $output, 'Contains teacher edited submission text');

        // Check that the teacher can submit the students work.
        $this->setUser($teacher);
        $this->submit_for_grading($student, $assignrecert, [], false);

        // Revert to draft so the student can edit it.
        $assignrecert->revert_to_draft($student->id);

        $this->setUser($student);

        // Check that the submission text was saved.
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringContainsString('Teacher edited submission text', $output, 'Contains student submission text');

        // Check that the student can submit their work.
        $this->submit_for_grading($student, $assignrecert, []);

        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringNotContainsString(get_string('addsubmission', 'assignrecert'), $output);

        // An editing teacher without the extra role should still be able to revert to draft.
        $this->setUser($otherteacher);

        // Revert to draft so the submission is editable.
        $assignrecert->revert_to_draft($student->id);
    }

    /**
     * Ensure that disabling submit after the cutoff date works as expected.
     */
    public function test_disable_submit_after_cutoff_date() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $now = time();
        $tomorrow = $now + DAYSECS;
        $lastweek = $now - (7 * DAYSECS);
        $yesterday = $now - DAYSECS;

        $this->setAdminUser();
        $assignrecert = $this->create_instance($course, [
                'duedate' => $yesterday,
                'cutoffdate' => $tomorrow,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Student should be able to see an add submission button.
        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assignrecert')));

        // Add a submission but don't submit now.
        $this->add_submission($student, $assignrecert);

        // Create another instance with cut-off and due-date already passed.
        $this->setAdminUser();
        $assignrecert = $this->create_instance($course, [
                'duedate' => $lastweek,
                'cutoffdate' => $yesterday,
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        $this->setUser($student);
        $output = $assignrecert->view_student_summary($student, true);
        $this->assertStringNotContainsString($output, get_string('editsubmission', 'assignrecert'),
                                 'Should not be able to edit after cutoff date.');
        $this->assertStringNotContainsString($output, get_string('submitassignmentrecert', 'assignrecert'),
                                 'Should not be able to submit after cutoff date.');
    }

    /**
     * Testing for submission comment plugin settings.
     *
     * @dataProvider submission_plugin_settings_provider
     * @param   bool    $globalenabled
     * @param   array   $instanceconfig
     * @param   bool    $isenabled
     */
    public function test_submission_comment_plugin_settings($globalenabled, $instanceconfig, $isenabled) {
        global $CFG;

        $course = $this->getDataGenerator()->create_course();

        $CFG->usecomments = $globalenabled;
        $assignrecert = $this->create_instance($course, $instanceconfig);
        $plugin = $assignrecert->get_submission_plugin_by_type('comments');
        $this->assertEquals($isenabled, (bool) $plugin->is_enabled('enabled'));
    }

    public function submission_plugin_settings_provider() {
        return [
            'CFG->usecomments true, empty config => Enabled by default' => [
                true,
                [],
                true,
            ],
            'CFG->usecomments true, config enabled => Comments enabled' => [
                true,
                [
                    'assignrecertsubmission_comments_enabled' => 1,
                ],
                true,
            ],
            'CFG->usecomments true, config idisabled => Comments enabled' => [
                true,
                [
                    'assignrecertsubmission_comments_enabled' => 0,
                ],
                true,
            ],
            'CFG->usecomments false, empty config => Disabled by default' => [
                false,
                [],
                false,
            ],
            'CFG->usecomments false, config enabled => Comments disabled' => [
                false,
                [
                    'assignrecertsubmission_comments_enabled' => 1,
                ],
                false,
            ],
            'CFG->usecomments false, config disabled => Comments disabled' => [
                false,
                [
                    'assignrecertsubmission_comments_enabled' => 0,
                ],
                false,
            ],
        ];
    }

    /**
     * Testing for comment inline settings
     */
    public function test_feedback_comment_commentinline() {
        global $CFG;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $sourcetext = "Hello!

I'm writing to you from the Moodle Majlis in Muscat, Oman, where we just had several days of Moodle community goodness.

URL outside a tag: https://moodle.org/logo/logo-240x60.gif
Plugin url outside a tag: @@PLUGINFILE@@/logo-240x60.gif

External link 1:<img src='https://moodle.org/logo/logo-240x60.gif' alt='Moodle'/>
External link 2:<img alt=\"Moodle\" src=\"https://moodle.org/logo/logo-240x60.gif\"/>
Internal link 1:<img src='@@PLUGINFILE@@/logo-240x60.gif' alt='Moodle'/>
Internal link 2:<img alt=\"Moodle\" src=\"@@PLUGINFILE@@logo-240x60.gif\"/>
Anchor link 1:<a href=\"@@PLUGINFILE@@logo-240x60.gif\" alt=\"bananas\">Link text</a>
Anchor link 2:<a title=\"bananas\" href=\"../logo-240x60.gif\">Link text</a>
";

        // Note the internal images have been stripped and the html is purified (quotes fixed in this case).
        $filteredtext = "Hello!

I'm writing to you from the Moodle Majlis in Muscat, Oman, where we just had several days of Moodle community goodness.

URL outside a tag: https://moodle.org/logo/logo-240x60.gif
Plugin url outside a tag: @@PLUGINFILE@@/logo-240x60.gif

External link 1:<img src=\"https://moodle.org/logo/logo-240x60.gif\" alt=\"Moodle\" />
External link 2:<img alt=\"Moodle\" src=\"https://moodle.org/logo/logo-240x60.gif\" />
Internal link 1:
Internal link 2:
Anchor link 1:Link text
Anchor link 2:<a title=\"bananas\" href=\"../logo-240x60.gif\">Link text</a>
";

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'assignrecertfeedback_comments_enabled' => 1,
                'assignrecertfeedback_comments_commentinline' => 1,
            ]);

        $this->setUser($student);

        // Add a submission but don't submit now.
        $this->add_submission($student, $assignrecert, $sourcetext);

        $this->setUser($teacher);

        $data = new stdClass();
        require_once($CFG->dirroot . '/mod/assignrecert/gradeform.php');
        $pagination = [
                'userid' => $student->id,
                'rownum' => 0,
                'last' => true,
                'useridlistid' => $assignrecert->get_useridlist_key_id(),
                'attemptnumber' => 0,
            ];
        $formparams = array($assignrecert, $data, $pagination);
        $mform = new mod_assignrecert_grade_form(null, [$assignrecert, $data, $pagination]);

        $this->assertEquals($filteredtext, $data->assignrecertfeedbackcomments_editor['text']);
    }

    /**
     * Testing for feedback comment plugin settings.
     *
     * @dataProvider feedback_plugin_settings_provider
     * @param   array   $instanceconfig
     * @param   bool    $isenabled
     */
    public function test_feedback_plugin_settings($instanceconfig, $isenabled) {
        $course = $this->getDataGenerator()->create_course();

        $assignrecert = $this->create_instance($course, $instanceconfig);
        $plugin = $assignrecert->get_feedback_plugin_by_type('comments');
        $this->assertEquals($isenabled, (bool) $plugin->is_enabled('enabled'));
    }

    public function feedback_plugin_settings_provider() {
        return [
            'No configuration => disabled' => [
                [],
                false,
            ],
            'Actively disabled' => [
                [
                    'assignrecertfeedback_comments_enabled' => 0,
                ],
                false,
            ],
            'Actively enabled' => [
                [
                    'assignrecertfeedback_comments_enabled' => 1,
                ],
                true,
            ],
        ];
    }

    /**
     * Testing if gradebook feedback plugin is enabled.
     */
    public function test_is_gradebook_feedback_enabled() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $adminconfig = get_config('assignrecert');
        $gradebookplugin = $adminconfig->feedback_plugin_for_gradebook;

        // Create assignmentrecert with gradebook feedback enabled and grade = 0.
        $assignrecert = $this->create_instance($course, [
                "{$gradebookplugin}_enabled" => 1,
                'grades' => 0,
            ]);

        // Get gradebook feedback plugin.
        $gradebookplugintype = str_replace('assignrecertfeedback_', '', $gradebookplugin);
        $plugin = $assignrecert->get_feedback_plugin_by_type($gradebookplugintype);
        $this->assertEquals(1, $plugin->is_enabled('enabled'));
        $this->assertEquals(1, $assignrecert->is_gradebook_feedback_enabled());
    }

    /**
     * Testing if gradebook feedback plugin is disabled.
     */
    public function test_is_gradebook_feedback_disabled() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $adminconfig = get_config('assignrecert');
        $gradebookplugin = $adminconfig->feedback_plugin_for_gradebook;

        // Create assignmentrecert with gradebook feedback disabled and grade = 0.
        $assignrecert = $this->create_instance($course, [
                "{$gradebookplugin}_enabled" => 0,
                'grades' => 0,
            ]);

        $gradebookplugintype = str_replace('assignrecertfeedback_', '', $gradebookplugin);
        $plugin = $assignrecert->get_feedback_plugin_by_type($gradebookplugintype);
        $this->assertEquals(0, $plugin->is_enabled('enabled'));
    }

    /**
     * Testing can_edit_submission.
     */
    public function test_can_edit_submission() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'submissiondrafts' => 1,
            ]);

        // Check student can edit their own submission.
        $this->assertTrue($assignrecert->can_edit_submission($student->id, $student->id));

        // Check student cannot edit others submission.
        $this->assertFalse($assignrecert->can_edit_submission($otherstudent->id, $student->id));

        // Check teacher cannot (by default) edit a students submission.
        $this->assertFalse($assignrecert->can_edit_submission($student->id, $teacher->id));
    }

    /**
     * Testing can_edit_submission with the editothersubmission capability.
     */
    public function test_can_edit_submission_with_editothersubmission() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'submissiondrafts' => 1,
            ]);

        // Add the required capability to edit a student submission.
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        assignrecert_capability('mod/assignrecert:editothersubmission', CAP_ALLOW, $roleid, $assignrecert->get_context()->id);
        role_assignrecert($roleid, $teacher->id, $assignrecert->get_context()->id);
        accesslib_clear_all_caches_for_unit_testing();

        // Check student can edit their own submission.
        $this->assertTrue($assignrecert->can_edit_submission($student->id, $student->id));

        // Check student cannot edit others submission.
        $this->assertFalse($assignrecert->can_edit_submission($otherstudent->id, $student->id));

        // Retest - should now have access.
        $this->assertTrue($assignrecert->can_edit_submission($student->id, $teacher->id));
    }

    /**
     * Testing can_edit_submission
     */
    public function test_can_edit_submission_separategroups() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student4 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group1->id);
        groups_add_member($group1, $student1);
        groups_add_member($group1, $student2);

        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group2->id);
        groups_add_member($group2, $student3);
        groups_add_member($group2, $student4);

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'submissiondrafts' => 1,
                'groupingid' => $grouping->id,
                'groupmode' => SEPARATEGROUPS,
            ]);

        // Verify a student does not have the ability to edit submissions for other users.
        $this->assertTrue($assignrecert->can_edit_submission($student1->id, $student1->id));
        $this->assertFalse($assignrecert->can_edit_submission($student2->id, $student1->id));
        $this->assertFalse($assignrecert->can_edit_submission($student3->id, $student1->id));
        $this->assertFalse($assignrecert->can_edit_submission($student4->id, $student1->id));
    }

    /**
     * Testing can_edit_submission
     */
    public function test_can_edit_submission_separategroups_with_editothersubmission() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student4 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group1->id);
        groups_add_member($group1, $student1);
        groups_add_member($group1, $student2);

        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group2->id);
        groups_add_member($group2, $student3);
        groups_add_member($group2, $student4);

        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
                'submissiondrafts' => 1,
                'groupingid' => $grouping->id,
                'groupmode' => SEPARATEGROUPS,
            ]);

        // Add the capability to the new assignmentrecert for student 1.
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        assignrecert_capability('mod/assignrecert:editothersubmission', CAP_ALLOW, $roleid, $assignrecert->get_context()->id);
        role_assignrecert($roleid, $student1->id, $assignrecert->get_context()->id);
        accesslib_clear_all_caches_for_unit_testing();

        // Verify student1 has the ability to edit submissions for other users in their group, but not other groups.
        $this->assertTrue($assignrecert->can_edit_submission($student1->id, $student1->id));
        $this->assertTrue($assignrecert->can_edit_submission($student2->id, $student1->id));
        $this->assertFalse($assignrecert->can_edit_submission($student3->id, $student1->id));
        $this->assertFalse($assignrecert->can_edit_submission($student4->id, $student1->id));

        // Verify other students do not have the ability to edit submissions for other users.
        $this->assertTrue($assignrecert->can_edit_submission($student2->id, $student2->id));
        $this->assertFalse($assignrecert->can_edit_submission($student1->id, $student2->id));
        $this->assertFalse($assignrecert->can_edit_submission($student3->id, $student2->id));
        $this->assertFalse($assignrecert->can_edit_submission($student4->id, $student2->id));
    }

    /**
     * Test if the view blind details capability works
     */
    public function test_can_view_blind_details() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'manager');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course, [
                'blindmarking' => 1,
            ]);

        $this->assertTrue($assignrecert->is_blind_marking());

        // Test student names are hidden to teacher.
        $this->setUser($teacher);
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertEquals(true, strpos($output, get_string('hiddenuser', 'assignrecert')));    // "Participant" is somewhere on the page.
        $this->assertEquals(false, strpos($output, fullname($student)));    // Students full name doesn't appear.

        // Test student names are visible to manager.
        $this->setUser($manager);
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertEquals(true, strpos($output, get_string('hiddenuser', 'assignrecert')));
        $this->assertEquals(true, strpos($output, fullname($student)));
    }

    /**
     * Testing get_shared_group_members
     */
    public function test_get_shared_group_members() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student4 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group1->id);
        groups_add_member($group1, $student1);
        groups_add_member($group1, $student2);

        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group2->id);
        groups_add_member($group2, $student3);
        groups_add_member($group2, $student4);

        $assignrecert = $this->create_instance($course, [
                'groupingid' => $grouping->id,
                'groupmode' => SEPARATEGROUPS,
            ]);

        $cm = $assignrecert->get_course_module();

        // Get shared group members for students 0 and 1.
        $groupmembers = $assignrecert->get_shared_group_members($cm, $student1->id);
        $this->assertCount(2, $groupmembers);
        $this->assertContainsEquals($student1->id, $groupmembers);
        $this->assertContainsEquals($student2->id, $groupmembers);

        $groupmembers = $assignrecert->get_shared_group_members($cm, $student2->id);
        $this->assertCount(2, $groupmembers);
        $this->assertContainsEquals($student1->id, $groupmembers);
        $this->assertContainsEquals($student2->id, $groupmembers);

        $groupmembers = $assignrecert->get_shared_group_members($cm, $student3->id);
        $this->assertCount(2, $groupmembers);
        $this->assertContainsEquals($student3->id, $groupmembers);
        $this->assertContainsEquals($student4->id, $groupmembers);

        $groupmembers = $assignrecert->get_shared_group_members($cm, $student4->id);
        $this->assertCount(2, $groupmembers);
        $this->assertContainsEquals($student3->id, $groupmembers);
        $this->assertContainsEquals($student4->id, $groupmembers);
    }

    /**
     * Testing get_shared_group_members
     */
    public function test_get_shared_group_members_override() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student4 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group1->id);
        groups_add_member($group1, $student1);
        groups_add_member($group1, $student2);

        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_assignrecert_grouping($grouping->id, $group2->id);
        groups_add_member($group2, $student3);
        groups_add_member($group2, $student4);

        $assignrecert = $this->create_instance($course, [
                'groupingid' => $grouping->id,
                'groupmode' => SEPARATEGROUPS,
            ]);

        $cm = $assignrecert->get_course_module();

        // Add the capability to access allgroups for one of the students.
        $roleid = create_role('Access all groups role', 'accessallgroupsrole', '');
        assignrecert_capability('moodle/site:accessallgroups', CAP_ALLOW, $roleid, $assignrecert->get_context()->id);
        role_assignrecert($roleid, $student1->id, $assignrecert->get_context()->id);
        accesslib_clear_all_caches_for_unit_testing();

        // Get shared group members for students 0 and 1.
        $groupmembers = $assignrecert->get_shared_group_members($cm, $student1->id);
        $this->assertCount(4, $groupmembers);
        $this->assertContainsEquals($student1->id, $groupmembers);
        $this->assertContainsEquals($student2->id, $groupmembers);
        $this->assertContainsEquals($student3->id, $groupmembers);
        $this->assertContainsEquals($student4->id, $groupmembers);

        $groupmembers = $assignrecert->get_shared_group_members($cm, $student2->id);
        $this->assertCount(2, $groupmembers);
        $this->assertContainsEquals($student1->id, $groupmembers);
        $this->assertContainsEquals($student2->id, $groupmembers);

        $groupmembers = $assignrecert->get_shared_group_members($cm, $student3->id);
        $this->assertCount(2, $groupmembers);
        $this->assertContainsEquals($student3->id, $groupmembers);
        $this->assertContainsEquals($student4->id, $groupmembers);

        $groupmembers = $assignrecert->get_shared_group_members($cm, $student4->id);
        $this->assertCount(2, $groupmembers);
        $this->assertContainsEquals($student3->id, $groupmembers);
        $this->assertContainsEquals($student4->id, $groupmembers);
    }

    /**
     * Test get plugins file areas
     */
    public function test_get_plugins_file_areas() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $assignrecert = $this->create_instance($course);

        // Test that all the submission and feedback plugins are returning the expected file aras.
        $usingfilearea = 0;
        $coreplugins = core_plugin_manager::standard_plugins_list('assignrecertsubmission');
        foreach ($assignrecert->get_submission_plugins() as $plugin) {
            $type = $plugin->get_type();
            if (!in_array($type, $coreplugins)) {
                continue;
            }
            $fileareas = $plugin->get_file_areas();

            if ($type == 'onlinetext') {
                $this->assertEquals(array('submissions_onlinetext' => 'Online text'), $fileareas);
                $usingfilearea++;
            } else if ($type == 'file') {
                $this->assertEquals(array('submission_files' => 'File submissions'), $fileareas);
                $usingfilearea++;
            } else {
                $this->assertEmpty($fileareas);
            }
        }
        $this->assertEquals(2, $usingfilearea);

        $usingfilearea = 0;
        $coreplugins = core_plugin_manager::standard_plugins_list('assignrecertfeedback');
        foreach ($assignrecert->get_feedback_plugins() as $plugin) {
            $type = $plugin->get_type();
            if (!in_array($type, $coreplugins)) {
                continue;
            }
            $fileareas = $plugin->get_file_areas();

            if ($type == 'editpdf') {
                $checkareas = [
                    'download' => 'Annotate PDF',
                    'combined' => 'Annotate PDF',
                    'importhtml' => 'Annotate PDF',
                    'pages' => 'Annotate PDF',
                    'readonlypages' => 'Annotate PDF',
                    'stamps' => 'Annotate PDF',
                ];
                $this->assertEquals($checkareas, $fileareas);
                $usingfilearea++;
            } else if ($type == 'file') {
                $this->assertEquals(array('feedback_files' => 'Feedback files'), $fileareas);
                $usingfilearea++;
            } else {
                $this->assertEmpty($fileareas);
            }
        }
        $this->assertEquals(2, $usingfilearea);
    }

    /**
     * Test override exists
     *
     * This function needs to obey the group override logic as per the assignrecert grading table and
     * the overview block.
     */
    public function test_override_exists() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);


        // Data:
        // - student1 => group A only
        // - student2 => group B only
        // - student3 => Group A + Group B (No user override)
        // - student4 => Group A + Group B (With user override)
        // - student4 => No groups.
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group1, $student1);

        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group2, $student2);

        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group1, $student3);
        groups_add_member($group2, $student3);

        $student4 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group1, $student4);
        groups_add_member($group2, $student4);

        $student5 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assignrecert = $this->create_instance($course);
        $instance = $assignrecert->get_instance();

        // Overrides for each of the groups, and a user override.
        $overrides = [
            (object) [
                // Override for group 1, highest priority (numerically lowest sortorder).
                'assignrecertid' => $instance->id,
                'groupid' => $group1->id,
                'userid' => null,
                'sortorder' => 1,
                'allowsubmissionsfromdate' => 1,
                'duedate' => 2,
                'cutoffdate' => 3
            ],
            (object) [
                // Override for group 2, lower priority (numerically higher sortorder).
                'assignrecertid' => $instance->id,
                'groupid' => $group2->id,
                'userid' => null,
                'sortorder' => 2,
                'allowsubmissionsfromdate' => 5,
                'duedate' => 6,
                'cutoffdate' => 6
            ],
            (object) [
                // User override.
                'assignrecertid' => $instance->id,
                'groupid' => null,
                'userid' => $student3->id,
                'sortorder' => null,
                'allowsubmissionsfromdate' => 7,
                'duedate' => 8,
                'cutoffdate' => 9
            ],
        ];

        foreach ($overrides as &$override) {
            $override->id = $DB->insert_record('assignrecert_overrides', $override);
        }

        // User only in group 1 should see the group 1 override.
        $this->assertEquals($overrides[0], $assignrecert->override_exists($student1->id));

        // User only in group 2 should see the group 2 override.
        $this->assertEquals($overrides[1], $assignrecert->override_exists($student2->id));

        // User only in both groups with an override should see the user override as it has higher priority.
        $this->assertEquals($overrides[2], $assignrecert->override_exists($student3->id));

        // User only in both groups with no override should see the group 1 override as it has higher priority.
        $this->assertEquals($overrides[0], $assignrecert->override_exists($student4->id));

        // User with no overrides shoudl get nothing.
        $override = $assignrecert->override_exists($student5->id);
        $this->assertNull($override->duedate);
        $this->assertNull($override->cutoffdate);
        $this->assertNull($override->allowsubmissionsfromdate);
    }

    /**
     * Test the quicksave grades processor
     */
    public function test_process_save_quick_grades() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'attemptreopenmethod' => ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_MANUAL,
            ]);

        // Initially grade the user.
        $grade = (object) [
                'attemptnumber' => '',
                'timemodified' => '',
            ];
        $data = [
                "grademodified_{$student->id}" => $grade->timemodified,
                "gradeattempt_{$student->id}" => $grade->attemptnumber,
                "quickgrade_{$student->id}" => '60.0',
            ];

        $result = $assignrecert->testable_process_save_quick_grades($data);
        $this->assertStringContainsString(get_string('quickgradingchangessaved', 'assignrecert'), $result);
        $grade = $assignrecert->get_user_grade($student->id, false);
        // TOTARA: required as string comparison of numeric values no longer coerces type.
        $this->assertSame('60.00000', $grade->grade);

        // Attempt to grade with a past attempts grade info.
        $assignrecert->testable_process_add_attempt($student->id);
        $data = array(
            'grademodified_' . $student->id => $grade->timemodified,
            'gradeattempt_' . $student->id => $grade->attemptnumber,
            'quickgrade_' . $student->id => '50.0'
        );
        $result = $assignrecert->testable_process_save_quick_grades($data);
        $this->assertStringContainsString(get_string('errorrecordmodified', 'assignrecert'), $result);
        $grade = $assignrecert->get_user_grade($student->id, false);
        $this->assertFalse($grade);

        // Attempt to grade a the attempt.
        $submission = $assignrecert->get_user_submission($student->id, false);
        $data = array(
            'grademodified_' . $student->id => '',
            'gradeattempt_' . $student->id => $submission->attemptnumber,
            'quickgrade_' . $student->id => '40.0'
        );
        $result = $assignrecert->testable_process_save_quick_grades($data);
        $this->assertStringContainsString(get_string('quickgradingchangessaved', 'assignrecert'), $result);
        $grade = $assignrecert->get_user_grade($student->id, false);
        // TOTARA: required as string comparison of numeric values no longer coerces type.
        $this->assertSame('40.00000', $grade->grade);

        // Catch grade update conflicts.
        // Save old data for later.
        $pastdata = $data;
        // Update the grade the 'good' way.
        $data = array(
            'grademodified_' . $student->id => $grade->timemodified,
            'gradeattempt_' . $student->id => $grade->attemptnumber,
            'quickgrade_' . $student->id => '30.0'
        );
        $result = $assignrecert->testable_process_save_quick_grades($data);
        $this->assertStringContainsString(get_string('quickgradingchangessaved', 'assignrecert'), $result);
        $grade = $assignrecert->get_user_grade($student->id, false);
        // TOTARA: required as string comparison of numeric values no longer coerces type.
        $this->assertEquals('30.00000', $grade->grade);

        // Now update using 'old' data. Should fail.
        $result = $assignrecert->testable_process_save_quick_grades($pastdata);
        $this->assertStringContainsString(get_string('errorrecordmodified', 'assignrecert'), $result);
        $grade = $assignrecert->get_user_grade($student->id, false);
        // TOTARA: required as string comparison of numeric values no longer coerces type.
        $this->assertEquals('30.00000', $grade->grade);
    }

    /**
     * Test updating activity completion when submitting an assessment.
     */
    public function test_update_activity_completion_records_solitary_submission() {

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'grade' => 100,
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'requireallteammemberssubmit' => 0,
            ]);
        $cm = $assignrecert->get_course_module();

        // Submit the assignmentrecert as the student.
        $this->add_submission($student, $assignrecert);

        // Totara: we have instant completion.
/*
        // Check that completion is not met yet.
        $completiondata = $completion->get_data($cm, false, $student->id);
        $this->assertEquals(0, $completiondata->completionstate);

        // Update to mark as complete.
        $submission = $assignrecert->get_user_submission($student->id, true);
        $assignrecert->testable_update_activity_completion_records(0, 0, $submission,
                $student->id, COMPLETION_COMPLETE, $completion);

*/
        $completion = new completion_info($course);

        // Completion should now be met.
        $completiondata = $completion->get_data($cm, false, $student->id);
        $this->assertEquals(1, $completiondata->completionstate);
    }

    /**
     * Test updating activity completion when submitting an assessment.
     */
    public function test_update_activity_completion_records_team_submission() {

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        groups_add_member($group1, $student);
        groups_add_member($group1, $otherstudent);

        $assignrecert = $this->create_instance($course, [
                'grade' => 100,
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'teamsubmission' => 1,
            ]);

        $cm = $assignrecert->get_course_module();

        $this->add_submission($student, $assignrecert);
        $this->submit_for_grading($student, $assignrecert, ['groupid' => $group1->id]);

        $completion = new completion_info($course);

        // Totara: we have instant complation
/*
        // Check that completion is not met yet.
        $completiondata = $completion->get_data($cm, false, $student->id);
        $this->assertEquals(0, $completiondata->completionstate);

        $completiondata = $completion->get_data($cm, false, $otherstudent->id);
        $this->assertEquals(0, $completiondata->completionstate);

        $submission = $assignrecert->get_user_submission($student->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $submission->groupid = $group1->id;

        $assignrecert->testable_update_activity_completion_records(1, 0, $submission, $student->id, COMPLETION_COMPLETE, $completion);
*/
        // Completion should now be met.
        $completiondata = $completion->get_data($cm, false, $student->id);
        $this->assertEquals(1, $completiondata->completionstate);

        $completiondata = $completion->get_data($cm, false, $otherstudent->id);
        $this->assertEquals(1, $completiondata->completionstate);
    }

    /**
     * Data provider for test_fix_null_grades
     * @return array[] Test data for test_fix_null_grades. Each element should contain grade, expectedcount and gradebookvalue
     */
    public function fix_null_grades_provider() {
        return [
            'Negative less than one is errant' => [
                'grade' => -0.64,
                'gradebookvalue' => null,
            ],
            'Negative more than one is errant' => [
                'grade' => -30.18,
                'gradebookvalue' => null,
            ],
            'Negative one exactly is not errant, but shouldn\'t be pushed to gradebook' => [
                'grade' => -1,
                'gradebookvalue' => null,
            ],
            'Positive grade is not errant' => [
                'grade' => 1,
                'gradebookvalue' => 1,
            ],
            'Large grade is not errant' => [
                'grade' => 100,
                'gradebookvalue' => 100,
            ],
            'Zero grade is not errant' => [
                'grade' => 0,
                'gradebookvalue' => 0,
            ],
        ];
    }

    /**
     * Test fix_null_grades
     * @param number $grade The grade we should set in the assignrecert grading table.
     * @param number $expectedcount The finalgrade we expect in the gradebook after fixing the grades.
     * @dataProvider fix_null_grades_provider
     */
    public function test_fix_null_grades($grade, $gradebookvalue) {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course);

        // Try getting a student's grade. This will give a grade of -1.
        // Then we can override it with a bad negative grade.
        $assignrecert->get_user_grade($student->id, true);

        // Set the grade to something errant.
        // We don't set the grader here, so we expect it to be -1 as a result.
        $DB->set_field(
            'assignrecert_grades',
            'grade',
            $grade,
            [
                'userid' => $student->id,
                'assignmentrecert' => $assignrecert->get_instance()->id,
            ]
        );
        $assignrecert->grade = $grade;
        $assignrecerttemp = clone $assignrecert->get_instance();
        $assignrecerttemp->cmidnumber = $assignrecert->get_course_module()->idnumber;
        assignrecert_update_grades($assignrecerttemp);

        // Check that the gradebook was updated with the assignrecert grade. So we can guarentee test results later on.
        $expectedgrade = $grade == -1 ? null : $grade; // Assign sends null to the gradebook for -1 grades.
        $gradegrade = grade_grade::fetch(array('userid' => $student->id, 'itemid' => $assignrecert->get_grade_item()->id));
        $this->assertEquals(-1, $gradegrade->usermodified);
        $this->assertEquals($expectedgrade, $gradegrade->rawgrade);

        // Call fix_null_grades().
        $method = new ReflectionMethod(assignrecert::class, 'fix_null_grades');
        $method->setAccessible(true);
        $result = $method->invoke($assignrecert);

        $this->assertSame(true, $result);

        $gradegrade = grade_grade::fetch(array('userid' => $student->id, 'itemid' => $assignrecert->get_grade_item()->id));

        $this->assertEquals(-1, $gradegrade->usermodified);
        $this->assertEquals($gradebookvalue, $gradegrade->finalgrade);

        // Check that the grade was updated in the gradebook by fix_null_grades.
        $this->assertEquals($gradebookvalue, $gradegrade->finalgrade);
    }

    /**
     * Test grade override displays 'Graded' for students
     */
    public function test_grade_submission_override() {
        global $DB, $PAGE, $OUTPUT;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_onlinetext_enabled' => 1,
            ]);

        // Simulate adding a grade.
        $this->setUser($teacher);
        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $student->id, 0);

        // Set grade override.
        $gradegrade = grade_grade::fetch([
                'userid' => $student->id,
                'itemid' => $assignrecert->get_grade_item()->id,
            ]);

        // Check that grade submission is not overridden yet.
        $this->assertEquals(false, $gradegrade->is_overridden());

        // Simulate a submission.
        $this->setUser($student);
        $submission = $assignrecert->get_user_submission($student->id, true);

        $PAGE->set_url(new moodle_url('/mod/assignrecert/view.php', ['id' => $assignrecert->get_course_module()->id]));

        // Set override grade grade, and check that grade submission has been overridden.
        $gradegrade->set_overridden(true);
        $this->assertEquals(true, $gradegrade->is_overridden());

        // Check that submissionslocked message 'This assignmentrecert is not accepting submissions' does not appear for student.
        $gradingtable = new assignrecert_grading_table($assignrecert, 1, '', 0, true);
        $output = $assignrecert->get_renderer()->render($gradingtable);
        $this->assertStringContainsString(get_string('submissionstatus_', 'assignrecert'), $output);

        $assignrecertsubmissionstatus = $assignrecert->get_assignrecert_submission_status_renderable($student, true);
        $output2 = $assignrecert->get_renderer()->render($assignrecertsubmissionstatus);

        // Check that submissionslocked 'This assignmentrecert is not accepting submissions' message does not appear for student.
        $this->assertStringNotContainsString(get_string('submissionslocked', 'assignrecert'), $output2);
        // Check that submissionstatus_marked 'Graded' message does appear for student.
        $this->assertStringContainsString(get_string('submissionstatus_marked', 'assignrecert'), $output2);
    }
}
