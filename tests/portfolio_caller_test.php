<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package mod_assignrecert
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
require_once($CFG->dirroot . '/mod/assignrecert/tests/fixtures/testable_assignrecertphp');

/**
 * Class mod_assignrecert_portfolio_caller_testcase
 *
 * Tests behaviour of the assignrecert_portfolio_caller class.
 */
class mod_assignrecert_portfolio_caller_testcase extends advanced_testcase {

    /**
     * Test an assignmentrecert file is loaded for a user who submitted it.
     */
    public function test_user_submission_file_is_loaded() {

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        /* @var \mod_assignrecert\testing\generator $assigngenerator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert');

        $activityrecord = $assigngenerator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_instance('assignrecert', $activityrecord->id);
        $context = context_module::instance($cm->id);
        $assignrecert = new mod_assignrecert_testable_assignrecert($context, $cm, $course);

        $submission = $assignrecert->get_user_submission($user->id, true);

        $fs = get_file_storage();
        $dummy = (object) array(
            'contextid' => $context->id,
            'component' => 'assignrecertsubmission_file',
            'filearea' => assignrecertsubMISSION_FILE_FILEAREA,
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'myassignmnent.pdf'
        );
        $file = $fs->create_file_from_string($dummy, 'Content of ' . $dummy->filename);

        $caller = new assignrecert_portfolio_caller(array('cmid' => $cm->id, 'fileid' => $file->get_id()));
        $caller->set('user', $user);
        $caller->load_data();
        $this->assertEquals($file->get_contenthash(), $caller->get_sha1_file());

        // This processes the file either by fileid or by other fields in the file table.
        // We should get the same outcome with either approach.
        $caller = new assignrecert_portfolio_caller(
            array(
                'cmid' => $cm->id,
                'sid' => $submission->id,
                'area' => assignrecertsubMISSION_FILE_FILEAREA,
                'component' => 'assignrecertsubmission_file',
            )
        );
        $caller->set('user', $user);
        $caller->load_data();
        $this->assertEquals($file->get_contenthash(), $caller->get_sha1_file());
    }

    /**
     * Test an assignmentrecert file is not loaded for a user that did not submit it.
     */
    public function test_different_user_submission_file_is_not_loaded() {

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        /* @var \mod_assignrecert\testing\generator $assigngenerator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert');

        $activityrecord = $assigngenerator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_instance('assignrecert', $activityrecord->id);
        $context = context_module::instance($cm->id);
        $assignrecert = new mod_assignrecert_testable_assignrecert($context, $cm, $course);

        $submission = $assignrecert->get_user_submission($user->id, true);

        $fs = get_file_storage();
        $dummy = (object) array(
            'contextid' => $context->id,
            'component' => 'assignrecertsubmission_file',
            'filearea' => assignrecertsubMISSION_FILE_FILEAREA,
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'myassignmnent.pdf'
        );
        $file = $fs->create_file_from_string($dummy, 'Content of ' . $dummy->filename);

        // Now add second user.
        $wronguser = $this->getDataGenerator()->create_user();

        $caller = new assignrecert_portfolio_caller(array('cmid' => $cm->id, 'fileid' => $file->get_id()));
        $caller->set('user', $wronguser);

        $this->expectException(portfolio_caller_exception::class);
        $this->expectExceptionMessage('Sorry, the requested file could not be found');

        $caller->load_data();
    }

    /**
     * Test an assignmentrecert file is loaded for a user who is part of a group that submitted it.
     */
    public function test_group_submission_file_is_loaded() {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $groupdata = new stdClass();
        $groupdata->courseid = $course->id;
        $groupdata->name = 'group1';
        $groupid = groups_create_group($groupdata);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        groups_add_member($groupid, $user);

        /* @var \mod_assignrecert\testing\generator $assigngenerator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert');

        $activityrecord = $assigngenerator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_instance('assignrecert', $activityrecord->id);
        $context = context_module::instance($cm->id);
        $assignrecert = new mod_assignrecert_testable_assignrecert($context, $cm, $course);

        $submission = $assignrecert->get_group_submission($user->id, $groupid, true);

        $fs = get_file_storage();
        $dummy = (object) array(
            'contextid' => $context->id,
            'component' => 'assignrecertsubmission_file',
            'filearea' => assignrecertsubMISSION_FILE_FILEAREA,
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'myassignmnent.pdf'
        );
        $file = $fs->create_file_from_string($dummy, 'Content of ' . $dummy->filename);

        $caller = new assignrecert_portfolio_caller(array('cmid' => $cm->id, 'fileid' => $file->get_id()));
        $caller->set('user', $user);
        $caller->load_data();
        $this->assertEquals($file->get_contenthash(), $caller->get_sha1_file());

        // This processes the file either by fileid or by other fields in the file table.
        // We should get the same outcome with either approach.
        $caller = new assignrecert_portfolio_caller(
            array(
                'cmid' => $cm->id,
                'sid' => $submission->id,
                'area' => assignrecertsubMISSION_FILE_FILEAREA,
                'component' => 'assignrecertsubmission_file',
            )
        );
        $caller->set('user', $user);
        $caller->load_data();
        $this->assertEquals($file->get_contenthash(), $caller->get_sha1_file());
    }

    /**
     * Test an assignmentrecert file is not loaded for a user who is not part of a group that submitted it.
     */
    public function test_different_group_submission_file_is_not_loaded() {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $groupdata = new stdClass();
        $groupdata->courseid = $course->id;
        $groupdata->name = 'group1';
        $groupid = groups_create_group($groupdata);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        groups_add_member($groupid, $user);

        /* @var \mod_assignrecert\testing\generator $assigngenerator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assignrecert');

        $activityrecord = $assigngenerator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_instance('assignrecert', $activityrecord->id);
        $context = context_module::instance($cm->id);
        $assignrecert = new mod_assignrecert_testable_assignrecert($context, $cm, $course);

        $submission = $assignrecert->get_group_submission($user->id, $groupid,true);

        $fs = get_file_storage();
        $dummy = (object) array(
            'contextid' => $context->id,
            'component' => 'assignrecertsubmission_file',
            'filearea' => assignrecertsubMISSION_FILE_FILEAREA,
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'myassignmnent.pdf'
        );
        $file = $fs->create_file_from_string($dummy, 'Content of ' . $dummy->filename);

        // Now add second user.
        $wronguser = $this->getDataGenerator()->create_user();

        // Create a new group for the wrong user.
        $groupdata = new stdClass();
        $groupdata->courseid = $course->id;
        $groupdata->name = 'group2';
        $groupid = groups_create_group($groupdata);
        $this->getDataGenerator()->enrol_user($wronguser->id, $course->id);
        groups_add_member($groupid, $wronguser);

        // In the negative test for the user, we loaded the caller via fileid. Switching to the other approach this time.
        $caller = new assignrecert_portfolio_caller(
            array(
                'cmid' => $cm->id,
                'sid' => $submission->id,
                'area' => assignrecertsubMISSION_FILE_FILEAREA,
                'component' => 'assignrecertsubmission_file',
            )
        );
        $caller->set('user', $wronguser);

        $this->expectException(portfolio_caller_exception::class);
        $this->expectExceptionMessage('Sorry, the requested file could not be found');

        $caller->load_data();
    }

}