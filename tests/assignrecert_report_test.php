<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package mod_assignrecert
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assignrecert/tests/base_test.php');

class mod_assignrecert_report_testcase extends mod_assignrecert_base_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    protected function setUp(): void {
        global $DB;

        parent::setUp();

        $this->create_extra_users();

        // Add additional default data (some real attempts and stuff).
        $this->setUser($this->editingteachers[0]);
        $this->create_instance();
        $assignrecert = $this->create_instance(
            array('duedate'                             => time(),
                  'attemptreopenmethod'                 => ASSIGNRECERT_ATTEMPT_REOPEN_METHOD_MANUAL,
                  'submissiondrafts'                    => 1,
                  'assignrecertsubmission_onlinetext_enabled' => 1)
        );

        //=== 1. Submitted, graded.
        $this->setUser($this->students[0]);
        $submission = $assignrecert->get_user_submission($this->students[0]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_HTML);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $this->students[0]->id, true, false);

        // Grade the submission.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '50.0';
        $assignrecert->testable_apply_grade_to_user($data, $this->students[0]->id, 0);

        //=== 2. Submitted, not graded.
        $this->setUser($this->students[1]);
        $submission = $assignrecert->get_user_submission($this->students[1]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_HTML);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $this->students[1]->id, true, false);

        //=== 3. Not submitted.
        $this->setUser($this->students[2]);
        $assignrecert->get_user_submission($this->students[2]->id, true); // This will just create submission records.

        //=== 4. Submitted, draft.
        $this->setUser($this->extrastudents[0]);
        $submission = $assignrecert->get_user_submission($this->extrastudents[0]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_HTML);
        $plugin = $assignrecert->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_DRAFT;
        $assignrecert->testable_update_submission($submission, $this->extrastudents[0]->id, true, false);

        // Create a second assignmentrecert without grading.
        $this->setUser($this->editingteachers[0]);
        $assignrecert = $this->create_instance(array('duedate' => time(), 'assignrecertsubmission_onlinetext_enabled' => 1));
        $DB->set_field('assignrecert', 'grade', 0, array('id' => $assignrecert->get_course_module()->instance));

        //=== 5. Submitted, no grading required.
        $this->setUser($this->extrastudents[1]);
        $submission = $assignrecert->get_user_submission($this->extrastudents[1]->id, true);
        $submission->status = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $assignrecert->testable_update_submission($submission, $this->extrastudents[1]->id, true, false);
    }

    public function test_assignrecert_report() {
        global $DB;

        $this->setAdminUser();

        $rid = $this->create_report('assignrecert', 'Assignment Recert report');

        $report = reportbuilder::create($rid, (new rb_config())->set_nocache(true));
        $this->add_column($report, 'assignmentrecert', 'name', null, null, null, 0);
        $this->add_column($report, 'assignmentrecert', 'status', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'base', 'grade', null, null, null, 0);

        $report = reportbuilder::create($rid); // Recreate after adding column.

        list($sql, $sqlparams, $cache) = $report->build_query(false, false, false);

        $records = array();
        $rs = $DB->get_recordset_sql($sql, $sqlparams);
        foreach ($rs as $record) {
            // Each user has one submission, so we can key by usernames.
            $records[$record->user_username] = $record;
        }

        self::assertCount(5, $records);

        self::assertEquals($records[$this->students[0]->username]->user_username, $this->students[0]->username);
        self::assertEquals($records[$this->students[0]->username]->assignmentrecert_status, 'graded');
        self::assertEquals($records[$this->students[0]->username]->base_grade, '50.00000');

        self::assertEquals($records[$this->students[1]->username]->user_username, $this->students[1]->username);
        self::assertEquals($records[$this->students[1]->username]->assignmentrecert_status, 'submitted');
        self::assertEmpty($records[$this->students[1]->username]->base_grade);

        self::assertEquals($records[$this->students[2]->username]->user_username, $this->students[2]->username);
        self::assertEquals($records[$this->students[2]->username]->assignmentrecert_status, 'notsubmitted');
        self::assertEmpty($records[$this->students[2]->username]->base_grade);

        self::assertEquals($records[$this->extrastudents[0]->username]->user_username, $this->extrastudents[0]->username);
        self::assertEquals($records[$this->extrastudents[0]->username]->assignmentrecert_status, 'draft');
        self::assertEmpty($records[$this->extrastudents[0]->username]->base_grade);

        self::assertEquals($records[$this->extrastudents[1]->username]->user_username, $this->extrastudents[1]->username);
        self::assertEquals($records[$this->extrastudents[1]->username]->assignmentrecert_status, 'submitted');
        self::assertEmpty($records[$this->extrastudents[1]->username]->base_grade);

        // Mock objects to use in the display function.
        $column = new rb_column('assignmentrecert', 'status', 'status', 'status', []);
        $row = new stdClass();

        // Testing display functions.
        $key = $this->students[0]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_status::display($records[$key]->assignmentrecert_status, 'html', $row, $column, $report);
        self::assertEquals('Graded', $display);

        $key = $this->students[1]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_status::display($records[$key]->assignmentrecert_status, 'html', $row, $column, $report);
        self::assertEquals('Submitted', $display);

        $key = $this->students[2]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_status::display($records[$key]->assignmentrecert_status, 'html', $row, $column, $report);
        self::assertEquals('Not submitted', $display);

        $key = $this->extrastudents[0]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_status::display($records[$key]->assignmentrecert_status, 'html', $row, $column, $report);
        self::assertEquals('Draft (not submitted)', $display);

        $key = $this->extrastudents[1]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_status::display($records[$key]->assignmentrecert_status, 'html', $row, $column, $report);
        self::assertEquals('Submitted', $display);

        $column = new rb_column('base', 'grade', 'grade', 'grade',
                                array('extrafields' => array('scale_values' => 'scale.scale', 'assignrecert_grade' => 'assignrecert.grade')));

        $key = $this->students[0]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_grade::display($records[$key]->base_grade, 'html', $records[$key], $column, $report);
        self::assertEquals(50, $display);

        $key = $this->students[1]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_grade::display($records[$key]->base_grade, 'html', $records[$key], $column, $report);
        self::assertEquals('No grade', $display);

        $key = $this->students[2]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_grade::display($records[$key]->base_grade, 'html', $records[$key], $column, $report);
        self::assertEquals('No grade', $display);

        $key = $this->extrastudents[0]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_grade::display($records[$key]->base_grade, 'html', $records[$key], $column, $report);
        self::assertEquals('No grade', $display);

        $key = $this->extrastudents[1]->username;
        $display = \mod_assignrecert\rb\display\assignrecert_submission_grade::display($records[$key]->base_grade, 'html', $records[$key], $column, $report);
        self::assertEquals('No grade required', $display);
    }
}
