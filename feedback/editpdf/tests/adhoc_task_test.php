<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Ning Zhou <ning.zhou@totara.com>
 * @package assignrecertfeedback_editpdf
 */

use assignrecertfeedback_editpdf\document_services;
use assignrecertfeedback_editpdf\task\remove_orphaned_editpdf_files;
use core_phpunit\testcase;

class totara_assignrecertfeedback_editpdf_task_testcase extends testcase {
    public function test_remove_orphaned_editpdf() {
        global $DB, $USER;

        $course_data = [
            'idnumber' => 't_c_1',
            'fullname' => 'Test Course',
            'summary' => 'Test Course description',
            'summaryformat' => FORMAT_MOODLE,
        ];
        $course = self::getDataGenerator()->create_course($course_data);

        $assignrecert_data = [
            'course' => $course->id,
            'name' => 'Test course assignmentrecert',
        ];
        $assignrecert = self::getDataGenerator()->create_module('assignrecert', $assignrecert_data);

        $student = self::getDataGenerator()->create_user();
        $assignrecert_grade_data = (object)[
            'assignmentrecert' => $assignrecert->id,
            'userid' => $student->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'grader' => $USER->id,
            'grade' => 50,
            'attemptnumber' => 0
        ];
        $assignrecert_grade_id = $DB->insert_record('assignrecert_grades', $assignrecert_grade_data);

        $fs = get_file_storage();
        $file_datas = [
            [
                'contextid' => 1,
                'component' => 'assignrecertfeedback_editpdf',
                'filearea' => document_services::STAMPS_FILEAREA,
                'itemid' => $assignrecert_grade_id,
                'filepath' => '/',
                'filename' => 'test_file_1.txt',
            ],
            [
                'contextid' => 1,
                'component' => 'assignrecertfeedback_editpdf',
                'filearea' => document_services::STAMPS_FILEAREA,
                'itemid' => 3, // Invalid assignrecert grade id
                'filepath' => '/',
                'filename' => 'test_file_2.txt',
            ],
            [
                'contextid' => 1,
                'component' => 'assignrecertfeedback_editpdf',
                'filearea' => document_services::STAMPS_FILEAREA,
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'test_file_3.txt',
            ],
            [
                'contextid' => 1,
                'component' => 'badges',
                'filearea' => document_services::STAMPS_FILEAREA,
                'itemid' => 3, // Invalid assignrecert grade id
                'filepath' => '/',
                'filename' => 'test_file_4.txt',
            ],
            [
                'contextid' => 1,
                'component' => 'assignrecertfeedback_editpdf',
                'filearea' => document_services::COMBINED_PDF_FILEAREA,
                'itemid' => 3, // Invalid assignrecert grade id
                'filepath' => '/',
                'filename' => 'test_file_5.txt',
            ],
            [
                'contextid' => 1,
                'component' => 'assignrecertfeedback_editpdf',
                'filearea' => document_services::FINAL_PDF_FILEAREA,
                'itemid' => $assignrecert_grade_id, // Invalid assignrecert grade id
                'filepath' => '/',
                'filename' => 'test_file_6.txt',
            ]
        ];
        // Create the files
        foreach ($file_datas as $key => $value) {
            $fs->create_file_from_string((object)$value, 'file ' . $key . ' contain this string.');
        }
        // Check file exist before adhoc task
        foreach ($file_datas as $value) {
            $this->assertTrue(
                $fs->file_exists(
                    $value['contextid'],
                    $value['component'],
                    $value['filearea'],
                    $value['itemid'],
                    $value['filepath'],
                    $value['filename'],
                )
            );
        }

        $task = new remove_orphaned_editpdf_files();
        $task->execute();

        $expect_result = [
            true,
            false,
            true,
            true,
            false,
            true
        ];

        // Check file exist after adhoc task
        foreach ($file_datas as $key => $value) {
            $this->assertEquals($expect_result[$key], $fs->file_exists(
                $value['contextid'],
                $value['component'],
                $value['filearea'],
                $value['itemid'],
                $value['filepath'],
                $value['filename'],
            ));
        }
    }
}
