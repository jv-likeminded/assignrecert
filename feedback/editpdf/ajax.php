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
 * Process ajax requests
 *
 * @package assignrecertfeedback_editpdf
 * @copyright  2012 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \assignrecertfeedback_editpdf\document_services;
use \assignrecertfeedback_editpdf\page_editor;
use \assignrecertfeedback_editpdf\comments_quick_list;

define('AJAX_SCRIPT', true);

require('../../../../config.php');
require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

require_sesskey();

$action = optional_param('action', '', PARAM_ALPHANUM);
$assignmentrecertid = required_param('assignmentrecertid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$attemptnumber = required_param('attemptnumber', PARAM_INT);
$readonly = optional_param('readonly', false, PARAM_BOOL);

$cm = \get_coursemodule_from_instance('assignrecert', $assignmentrecertid, 0, false, MUST_EXIST);
$context = \context_module::instance($cm->id);

$assignmentrecert = new \assignrecert($context, null, null);

require_login($assignmentrecert->get_course(), false, $cm);

if (!$assignmentrecert->can_view_submission($userid)) {
    print_error('nopermission');
}

if ($action == 'loadallpages') {
    $draft = true;
    if (!has_capability('mod/assignrecert:grade', $context)) {
        $draft = false;
        $readonly = true; // A student always sees the readonly version.
        require_capability('mod/assignrecert:submit', $context);
    }

    // Whoever is viewing the readonly version should not use the drafts, but the actual annotations.
    if ($readonly) {
        $draft = false;
    }

    $pages = document_services::get_page_images_for_attempt($assignmentrecert,
                                                            $userid,
                                                            $attemptnumber,
                                                            $readonly);

    $response = new stdClass();
    $response->pagecount = count($pages);
    $response->pages = array();

    $grade = $assignmentrecert->get_user_grade($userid, true, $attemptnumber);

    // The readonly files are stored in a different file area.
    $filearea = document_services::PAGE_IMAGE_FILEAREA;
    if ($readonly) {
        $filearea = document_services::PAGE_IMAGE_READONLY_FILEAREA;
    }

    foreach ($pages as $id => $pagefile) {
        $index = count($response->pages);
        $page = new stdClass();
        $comments = page_editor::get_comments($grade->id, $index, $draft);
        $page->url = moodle_url::make_pluginfile_url($context->id,
                                                     'assignrecertfeedback_editpdf',
                                                     $filearea,
                                                     $grade->id,
                                                     '/',
                                                     $pagefile->get_filename())->out();
        $page->comments = $comments;
        if ($imageinfo = $pagefile->get_imageinfo()) {
            $page->width = $imageinfo['width'];
            $page->height = $imageinfo['height'];
        } else {
            $page->width = 0;
            $page->height = 0;
        }
        $annotations = page_editor::get_annotations($grade->id, $index, $draft);
        $page->annotations = $annotations;
        array_push($response->pages, $page);
    }

    echo json_encode($response);
    die();
} else if ($action == 'savepage') {
    require_capability('mod/assignrecert:grade', $context);

    $response = new stdClass();
    $response->errors = array();

    $grade = $assignmentrecert->get_user_grade($userid, true, $attemptnumber);

    $pagejson = required_param('page', PARAM_RAW);
    $page = json_decode($pagejson);
    $index = required_param('index', PARAM_INT);

    $added = page_editor::set_comments($grade->id, $index, $page->comments);
    if ($added != count($page->comments)) {
        array_push($response->errors, get_string('couldnotsavepage', 'assignrecertfeedback_editpdf', $index+1));
    }
    $added = page_editor::set_annotations($grade->id, $index, $page->annotations);
    if ($added != count($page->annotations)) {
        array_push($response->errors, get_string('couldnotsavepage', 'assignrecertfeedback_editpdf', $index+1));
    }
    echo json_encode($response);
    die();

} else if ($action == 'generatepdf') {

    require_capability('mod/assignrecert:grade', $context);
    $response = new stdClass();
    $grade = $assignmentrecert->get_user_grade($userid, true, $attemptnumber);
    $file = document_services::generate_feedback_document($assignmentrecert, $userid, $attemptnumber);

    $response->url = '';
    if ($file) {
        $url = moodle_url::make_pluginfile_url($assignmentrecert->get_context()->id,
                                               'assignrecertfeedback_editpdf',
                                               document_services::FINAL_PDF_FILEAREA,
                                               $grade->id,
                                               '/',
                                               $file->get_filename(),
                                               false);
        $response->url = $url->out(true);
        $response->filename = $file->get_filename();
    }

    echo json_encode($response);
    die();
} else if ($action == 'loadquicklist') {
    require_capability('mod/assignrecert:grade', $context);

    $result = comments_quick_list::get_comments();

    echo json_encode($result);
    die();

} else if ($action == 'addtoquicklist') {
    require_capability('mod/assignrecert:grade', $context);

    $comment = required_param('commenttext', PARAM_RAW);
    $width = required_param('width', PARAM_INT);
    $colour = required_param('colour', PARAM_ALPHA);

    $result = comments_quick_list::add_comment($comment, $width, $colour);

    echo json_encode($result);
    die();
} else if ($action == 'revertchanges') {
    require_capability('mod/assignrecert:grade', $context);

    $grade = $assignmentrecert->get_user_grade($userid, true, $attemptnumber);

    $result = page_editor::revert_drafts($gradeid);

    echo json_encode($result);
    die();
} else if ($action == 'removefromquicklist') {
    require_capability('mod/assignrecert:grade', $context);

    $commentid = required_param('commentid', PARAM_INT);

    $result = comments_quick_list::remove_comment($commentid);

    echo json_encode($result);
    die();
} else if ($action == 'deletefeedbackdocument') {
    require_capability('mod/assignrecert:grade', $context);

    $grade = $assignmentrecert->get_user_grade($userid, true, $attemptnumber);
    $result = document_services::delete_feedback_document($assignmentrecert, $userid, $attemptnumber);

    $result = $result && page_editor::unrelease_drafts($grade->id);
    echo json_encode($result);
    die();
}

