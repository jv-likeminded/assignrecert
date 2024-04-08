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
 * This file contains the restore code for the feedback_file plugin.
 *
 * @package   assignrecertfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Restore subplugin class.
 *
 * Provides the necessary information needed
 * to restore one assignrecert_feedback subplugin.
 *
 * @package   assignrecertfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_assignrecertfeedback_file_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the subplugin at assignmentrecert level
     * @return array
     */
    protected function define_grade_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('grade');
        // We used get_recommended_name() so this works.
        $elepath = $this->get_pathfor('/feedback_file');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Processes one feedback_file element
     * @param mixed $data
     */
    public function process_assignrecertfeedback_file_grade($data) {
        global $DB;

        $data = (object)$data;
        $data->assignmentrecert = $this->get_new_parentid('assignrecert');
        $oldgradeid = $data->grade;
        // The mapping is set in the restore for the core assignrecert activity
        // when a grade node is processed.
        $data->grade = $this->get_mappingid('grade', $data->grade);

        $DB->insert_record('assignrecertfeedback_file', $data);

        $this->add_related_files('assignrecertfeedback_file', 'feedback_files', 'grade', null, $oldgradeid);
    }

}
