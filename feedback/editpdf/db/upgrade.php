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
 * Upgrade code for the feedback_editpdf module.
 *
 * @package   assignrecertfeedback_editpdf
 * @copyright 2013 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * EditPDF upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignrecertfeedback_editpdf_upgrade($oldversion) {
    global $CFG, $DB;

//    $dbman = $DB->get_manager();

    if ($oldversion < 2022110801) {
        $task = new \assignrecertfeedback_editpdf\task\remove_orphaned_editpdf_files();
        \core\task\manager::queue_adhoc_task($task);

        upgrade_plugin_savepoint(true, 2022110801, 'assignrecertfeedback', 'editpdf');
    }

    // Totara 13.0 release line.

    return true;
}
