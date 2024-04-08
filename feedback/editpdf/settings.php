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
 * Settings for assignrecertfeedback PDF plugin
 *
 * @package   assignrecertfeedback_editpdf
 * @copyright 2013 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Stamp files setting.
$name = 'assignrecertfeedback_editpdf/stamps';
$title = get_string('stamps','assignrecertfeedback_editpdf');
$description = get_string('stampsdesc', 'assignrecertfeedback_editpdf');

$setting = new admin_setting_configstoredfile($name, $title, $description, 'stamps', 0,
    array('maxfiles' => 8, 'accepted_types' => array('image')));
$settings->add($setting);

// Ghostscript setting.
$systempathslink = new moodle_url('/admin/settings.php', array('section' => 'systempaths'));
$systempathlink = html_writer::link($systempathslink, get_string('systempaths', 'admin'));
$settings->add(new admin_setting_heading('pathtogs', get_string('pathtogs', 'admin'),
        get_string('pathtogspathdesc', 'assignrecertfeedback_editpdf', $systempathlink)));

$url = new moodle_url('/mod/assignrecert/feedback/editpdf/testgs.php');
$link = html_writer::link($url, get_string('testgs', 'assignrecertfeedback_editpdf'));
$settings->add(new admin_setting_heading('testgs', '', $link));

// Totara: it is not secure to use unoconv on servers!
/*
// Unoconv setting.
$systempathslink = new moodle_url('/admin/settings.php', array('section' => 'systempaths'));
$systempathlink = html_writer::link($systempathslink, get_string('systempaths', 'admin'));
$settings->add(new admin_setting_heading('pathtounoconv', get_string('pathtounoconv', 'admin'),
    get_string('pathtounoconvpathdesc', 'assignrecertfeedback_editpdf', $systempathlink)));

$url = new moodle_url('/mod/assignrecert/feedback/editpdf/testunoconv.php');
$link = html_writer::link($url, get_string('test_unoconv', 'assignrecertfeedback_editpdf'));
$settings->add(new admin_setting_heading('test_unoconv', '', $link));
*/
