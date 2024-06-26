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
 * This file contains the moodle hooks for the submission comments plugin
 *
 * @package   assignrecertsubmission_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 *
 * Callback method for data validation---- required method for AJAXmoodle based comment API
 *
 * @param stdClass $options
 * @return bool
 */
function assignrecertsubmission_comments_comment_validate(stdClass $options) {
    global $USER, $CFG, $DB;

    if ($options->commentarea != 'submission_comments' &&
            $options->commentarea != 'submission_comments_upgrade') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$submission = $DB->get_record('assignrecert_submission', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;

    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    $assignmentrecert = new assignrecert($context, null, null);

    if ($assignmentrecert->get_instance()->id != $submission->assignmentrecert) {
        throw new comment_exception('invalidcontext');
    }

    return true;
}

/**
 * Permission control method for submission plugin ---- required method for AJAXmoodle based comment API
 *
 * @param stdClass $options
 * @return array
 */
function assignrecertsubmission_comments_comment_permissions(stdClass $options) {
    global $USER, $CFG, $DB;

    if ($options->commentarea != 'submission_comments' &&
            $options->commentarea != 'submission_comments_upgrade') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$submission = $DB->get_record('assignrecert_submission', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;

    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    $assignmentrecert = new assignrecert($context, null, null);

    if ($assignmentrecert->get_instance()->id != $submission->assignmentrecert) {
        throw new comment_exception('invalidcontext');
    }

    if ($assignmentrecert->get_instance()->teamsubmission &&
        !$assignmentrecert->can_view_group_submission($submission->groupid)) {
        return array('post' => false, 'view' => false);
    }

    if (!$assignmentrecert->get_instance()->teamsubmission &&
        !$assignmentrecert->can_view_submission($submission->userid)) {
        return array('post' => false, 'view' => false);
    }

    return array('post' => true, 'view' => true);
}

/**
 * Callback called by comment::get_comments() and comment::add(). Gives an opportunity to enforce blind-marking.
 *
 * @param array $comments
 * @param stdClass $options
 * @return array
 * @throws comment_exception
 */
function assignrecertsubmission_comments_comment_display($comments, $options) {
    global $CFG, $DB, $USER;

    if ($options->commentarea != 'submission_comments' &&
        $options->commentarea != 'submission_comments_upgrade') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$submission = $DB->get_record('assignrecert_submission', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;
    $cm = $options->cm;
    $course = $options->courseid;

    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    $assignmentrecert = new assignrecert($context, $cm, $course);

    if ($assignmentrecert->get_instance()->id != $submission->assignmentrecert) {
        throw new comment_exception('invalidcontext');
    }

    if ($assignmentrecert->is_blind_marking() && !empty($comments)) {
        // Blind marking is being used, may need to map unique anonymous ids to the comments.
        $usermappings = array();
        $guestuser = guest_user();

        // Check group users first.
        $userinteam = false;
        if ($assignmentrecert->get_instance()->teamsubmission && has_capability('mod/assignrecert:submit', $context)) {
            $assignmentrecert->set_course(get_course($course));

            $members = $assignmentrecert->get_submission_group_members($submission->groupid, true);
            foreach ($members as $member) {
                if ($assignmentrecert->can_edit_submission($member->id)) {
                    $userinteam = true;
                    break;
                }
            }
        }

        foreach ($comments as $comment) {

            if (has_capability('mod/assignrecert:viewblinddetails', $context) && $USER->id != $comment->userid) {
                $anonid = $assignmentrecert->get_uniqueid_for_user($comment->userid);
                // Show participant information and the user's full name to users with the view blind details capability.
                $a = new stdClass();
                $a->participantnumber = $anonid;
                $a->participantfullname = $comment->fullname;
                $comment->fullname = get_string('blindmarkingviewfullname', 'assignrecertsubmission_comments', $a);
            } else if ($USER->id == $comment->userid || $submission->userid == $USER->id || $userinteam) { //@codingStandardsIgnoreLine
                // Do not anonymize the user details for this comment.
            } else {
                // Anonymize the comments.
                if (empty($usermappings[$comment->userid])) {
                    $anonid = $assignmentrecert->get_uniqueid_for_user($comment->userid);
                    // The blind-marking information for this commenter has not been generated; do so now.
                    $commenter = new stdClass();
                    $commenter->firstname = get_string('blindmarkingname', 'assignrecertsubmission_comments', $anonid);
                    $commenter->lastname = '';
                    $commenter->firstnamephonetic = '';
                    $commenter->lastnamephonetic = '';
                    $commenter->middlename = '';
                    $commenter->alternatename = '';
                    $commenter->picture = 0;
                    $commenter->id = $guestuser->id;
                    // TOTARA - Escape potential XSS in user email.
                    $commenter->email = clean_string($guestuser->email);
                    $commenter->imagealt = $guestuser->imagealt;

                    // Temporarily store blind-marking information for use in later comments if necessary.
                    $usermappings[$comment->userid] = new stdClass();
                    $usermappings[$comment->userid]->fullname = fullname($commenter);
                    $usermappings[$comment->userid]->avatar = $assignmentrecert->get_renderer()->user_picture($commenter,
                            array('size' => 18, 'link' => false));
                }

                // Set blind-marking information for this comment.
                $comment->fullname = $usermappings[$comment->userid]->fullname;
                $comment->avatar = $usermappings[$comment->userid]->avatar;
                $comment->profileurl = null;
            }
        }
    }

    return $comments;
}

/**
 * Callback to force the userid for all comments to be the userid of the submission and NOT the global $USER->id. This
 * is required by the upgrade code. Note the comment area is used to identify upgrades.
 *
 * @param stdClass $comment
 * @param stdClass $param
 */
function assignrecertsubmission_comments_comment_add(stdClass $comment, stdClass $param) {

    global $DB;
    if ($comment->commentarea == 'submission_comments_upgrade') {
        $submissionid = $comment->itemid;
        $submission = $DB->get_record('assignrecert_submission', array('id' => $submissionid));

        $comment->userid = $submission->userid;
        $comment->commentarea = 'submission_comments';
    }
}

