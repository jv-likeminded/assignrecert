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
 * Tests for mod/assignrecert/submission/file/locallib.php
 *
 * @package   assignrecertsubmission_file
 * @copyright 2016 Cameron Ball
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assignrecert/tests/generator.php');

/**
 * Unit tests for mod/assignrecert/submission/file/locallib.php
 *
 * @copyright  2016 Cameron Ball
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignrecertsubmission_file_locallib_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_assignrecert_test_generator;

    /**
     * Test submission_is_empty
     *
     * @dataProvider submission_is_empty_testcases
     * @param string $data The file submission data
     * @param bool $expected The expected return value
     */
    public function test_submission_is_empty($data, $expected) {

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_file_enabled' => 1,
                'assignrecertsubmission_file_maxfiles' => 12,
                'assignrecertsubmission_file_maxsizebytes' => 10,
            ]);

        $this->setUser($student->id);

        $itemid = file_get_unused_draft_itemid();
        $submission = (object)['files_filemanager' => $itemid];
        $plugin = $assignrecert->get_submission_plugin_by_type('file');

        if ($data) {
            $data += ['contextid' => context_user::instance($student->id)->id, 'itemid' => $itemid];
            $fs = get_file_storage();
            $fs->create_file_from_string((object)$data, 'Content of ' . $data['filename']);
        }

        $result = $plugin->submission_is_empty($submission);
        $this->assertTrue($result === $expected);
    }

    /**
     * Test that an empty directory is is not detected as a valid submission by submission_is_empty.
     */
    public function test_submission_is_empty_directory_only() {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_file_enabled' => 1,
                'assignrecertsubmission_file_maxfiles' => 12,
                'assignrecertsubmission_file_maxsizebytes' => 10,
            ]);
        $this->setUser($student->id);
        $itemid = file_get_unused_draft_itemid();
        $submission = (object)['files_filemanager' => $itemid];
        $plugin = $assignrecert->get_submission_plugin_by_type('file');
        $fs = get_file_storage();
        $fs->create_directory(
                context_user::instance($student->id)->id,
                'user',
                'draft',
                $itemid,
                '/subdirectory/'
        );

        $this->assertTrue($plugin->submission_is_empty($submission));
    }

    /**
     * Test new_submission_empty
     *
     * @dataProvider submission_is_empty_testcases
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
            ]);

        $this->setUser($student);

        $itemid = file_get_unused_draft_itemid();
        $submission = (object) ['files_filemanager' => $itemid];

        if ($data) {
            $data += ['contextid' => context_user::instance($student->id)->id, 'itemid' => $itemid];
            $fs = get_file_storage();
            $fs->create_file_from_string((object)$data, 'Content of ' . $data['filename']);
        }

        $result = $assignrecert->new_submission_empty($submission);
        $this->assertTrue($result === $expected);
    }

    /**
     * Test that an empty directory is is not detected as a valid submission by new_submission_is_empty.
     */
    public function test_new_submission_empty_directory_only() {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignrecert = $this->create_instance($course, [
                'assignrecertsubmission_file_enabled' => 1,
                'assignrecertsubmission_file_maxfiles' => 12,
                'assignrecertsubmission_file_maxsizebytes' => 10,
            ]);
        $this->setUser($student->id);
        $itemid = file_get_unused_draft_itemid();
        $submission = (object)['files_filemanager' => $itemid];
        $plugin = $assignrecert->get_submission_plugin_by_type('file');
        $fs = get_file_storage();
        $fs->create_directory(
                context_user::instance($student->id)->id,
                'user',
                'draft',
                $itemid,
                '/subdirectory/'
        );

        $this->assertTrue($assignrecert->new_submission_empty($submission));
    }

    /**
     * Dataprovider for the test_submission_is_empty testcase
     *
     * @return array of testcases
     */
    public function submission_is_empty_testcases() {
        return [
            'With file' => [
                [
                    'component' => 'user',
                    'filearea' => 'draft',
                    'filepath' => '/',
                    'filename' => 'not_a_virus.exe'
                ],
                false
            ],
            'With file in directory' => [
                [
                    'component' => 'user',
                    'filearea' => 'draft',
                    'filepath' => '/subdir/',
                    'filename' => 'not_a_virus.exe'
                ],
                false
            ],
            'Without file' => [null, true]
        ];
    }
}
