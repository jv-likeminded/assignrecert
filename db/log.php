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
 * Definition of log events
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'assignrecert', 'action'=>'add', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'delete mod', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'download all submissions', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'grade submission', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'lock submission', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'reveal identities', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'revert submission to draft', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'set marking workflow state', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'submission statement accepted', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'submit', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'submit for grading', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'unlock submission', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'update', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'upload', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view all', 'mtable'=>'course', 'field'=>'fullname'),
    array('module'=>'assignrecert', 'action'=>'view confirm submit assignment form', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view grading form', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view submission', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view submission grading table', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view submit assignmentrecert form', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view feedback', 'mtable'=>'assignrecert', 'field'=>'name'),
    array('module'=>'assignrecert', 'action'=>'view batch set marking workflow state', 'mtable'=>'assignrecert', 'field'=>'name'),
);
