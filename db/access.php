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
 * Capability definitions for this module.
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = array(

    'mod/assignrecert:view' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        )
    ),

    'mod/assignrecert:submit' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW
        )
    ),

    'mod/assignrecert:grade' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        )
    ),

    'mod/assignrecert:exportownsubmission' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        )
    ),

    'mod/assignrecert:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    'mod/assignrecert:editothersubmission' => array(
        'riskbitmask' => RISK_MANAGETRUST|RISK_DATALOSS|RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE
    ),

    'mod/assignrecert:grantextension' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'gradereport/grader:view'
    ),

    'mod/assignrecert:revealidentities' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        )
    ),


    'mod/assignrecert:reviewgrades' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'moodle/grade:manage'
    ),

    'mod/assignrecert:releasegrades' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'moodle/grade:manage'
    ),

    'mod/assignrecert:managegrades' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'moodle/grade:manage'
    ),

    'mod/assignrecert:manageallocations' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'moodle/grade:manage'
    ),

    'mod/assignrecert:viewgrades' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        )
    ),

    'mod/assignrecert:viewblinddetails' => array(
        'riskbitmask' => RISK_PERSONAL,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        )
    ),

    'mod/assignrecert:receivegradernotifications' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        )
    ),

    // Edit the assignrecert overrides.
    'mod/assignrecert:manageoverrides' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        )
    ),
);

