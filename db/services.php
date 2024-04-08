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
 * Web service for mod assignrecert
 * @package    mod_assignrecert
 * @subpackage db
 * @since      Moodle 2.4
 * @copyright  2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

        'mod_assignrecert_copy_previous_attempt' => array(
            'classname'     => 'mod_assignrecert_external',
            'methodname'    => 'copy_previous_attempt',
            'classpath'     => 'mod/assignrecert/externallib.php',
            'description'   => 'Copy a students previous attempt to a new attempt.',
            'type'          => 'write',
            'capabilities'  => 'mod/assignrecert:view, mod/assignrecert:submit'
        ),

        'mod_assignrecert_get_grades' => array(
                'classname'   => 'mod_assignrecert_external',
                'methodname'  => 'get_grades',
                'classpath'   => 'mod/assignrecert/externallib.php',
                'description' => 'Returns grades from the assignmentrecert',
                'type'        => 'read',
                'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_get_assignmentrecerts' => array(
                'classname'   => 'mod_assignrecert_external',
                'methodname'  => 'get_assignmentrecerts',
                'classpath'   => 'mod/assignrecert/externallib.php',
                'description' => 'Returns the courses and assignmentrecerts for the users capability',
                'type'        => 'read',
                'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_get_submissions' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'get_submissions',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Returns the submissions for assignmentrecerts',
                'type' => 'read',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_get_user_flags' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'get_user_flags',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Returns the user flags for assignmentrecerts',
                'type' => 'read',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_set_user_flags' => array(
                'classname'   => 'mod_assignrecert_external',
                'methodname'  => 'set_user_flags',
                'classpath'   => 'mod/assignrecert/externallib.php',
                'description' => 'Creates or updates user flags',
                'type'        => 'write',
                'capabilities'=> 'mod/assignrecert:grade',
                'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_get_user_mappings' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'get_user_mappings',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Returns the blind marking mappings for assignmentrecerts',
                'type' => 'read',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_revert_submissions_to_draft' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'revert_submissions_to_draft',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Reverts the list of submissions to draft status',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_lock_submissions' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'lock_submissions',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Prevent students from making changes to a list of submissions',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_unlock_submissions' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'unlock_submissions',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Allow students to make changes to a list of submissions',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_save_submission' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'save_submission',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Update the current students submission',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_submit_for_grading' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'submit_for_grading',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Submit the current students assignmentrecert for grading',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_save_grade' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'save_grade',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Save a grade update for a single student.',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_save_grades' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'save_grades',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Save multiple grade updates for an assignmentrecert.',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_save_user_extensions' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'save_user_extensions',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Save a list of assignmentrecert extensions',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_reveal_identities' => array(
                'classname' => 'mod_assignrecert_external',
                'methodname' => 'reveal_identities',
                'classpath' => 'mod/assignrecert/externallib.php',
                'description' => 'Reveal the identities for a blind marking assignmentrecert',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_view_grading_table' => array(
                'classname'     => 'mod_assignrecert_external',
                'methodname'    => 'view_grading_table',
                'classpath'     => 'mod/assignrecert/externallib.php',
                'description'   => 'Trigger the grading_table_viewed event.',
                'type'          => 'write',
                'capabilities'  => 'mod/assignrecert:view, mod/assignrecert:viewgrades',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_view_submission_status' => array(
            'classname'     => 'mod_assignrecert_external',
            'methodname'    => 'view_submission_status',
            'classpath'     => 'mod/assignrecert/externallib.php',
            'description'   => 'Trigger the submission status viewed event.',
            'type'          => 'write',
            'capabilities'  => 'mod/assignrecert:view',
            'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_get_submission_status' => array(
            'classname'     => 'mod_assignrecert_external',
            'methodname'    => 'get_submission_status',
            'classpath'     => 'mod/assignrecert/externallib.php',
            'description'   => 'Returns information about an assignmentrecert submission status for a given user.',
            'type'          => 'read',
            'capabilities'  => 'mod/assignrecert:view',
            'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_list_participants' => array(
                'classname'     => 'mod_assignrecert_external',
                'methodname'    => 'list_participants',
                'classpath'     => 'mod/assignrecert/externallib.php',
                'description'   => 'List the participants for a single assignmentrecert, with some summary info about their submissions.',
                'type'          => 'read',
                'ajax'          => true,
                'capabilities'  => 'mod/assignrecert:view, mod/assignrecert:viewgrades',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_assignrecert_submit_grading_form' => array(
                'classname'     => 'mod_assignrecert_external',
                'methodname'    => 'submit_grading_form',
                'classpath'     => 'mod/assignrecert/externallib.php',
                'description'   => 'Submit the grading form data via ajax',
                'type'          => 'write',
                'ajax'          => true,
                'capabilities'  => 'mod/assignrecert:grade',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),
        'mod_assignrecert_get_participant' => array(
                'classname'     => 'mod_assignrecert_external',
                'methodname'    => 'get_participant',
                'classpath'     => 'mod/assignrecert/externallib.php',
                'description'   => 'Get a participant for an assignmentrecert, with some summary info about their submissions.',
                'type'          => 'read',
                'ajax'          => true,
                'capabilities'  => 'mod/assignrecert:view, mod/assignrecert:viewgrades',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),
        'mod_assignrecert_view_assign' => array(
            'classname'     => 'mod_assignrecert_external',
            'methodname'    => 'view_assign',
            'classpath'     => 'mod/assignrecert/externallib.php',
            'description'   => 'Update the module completion status.',
            'type'          => 'write',
            'capabilities'  => 'mod/assignrecert:view',
            'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),
);
