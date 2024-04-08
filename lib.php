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
 * This file contains the moodle hooks for the assignrecert module.
 *
 * It delegates most functions to the assignmentrecert class.
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Adds an assignmentrecert instance
 *
 * This is done by calling the add_instance() method of the assignmentrecert type class
 * @param stdClass $data
 * @param mod_assignrecert_mod_form $form
 * @return int The instance id of the new assignmentrecert
 */
function assignrecert_add_instance(stdClass $data, mod_assignrecert_mod_form $form = null) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $assignmentrecert = new assignrecert(context_module::instance($data->coursemodule), null, null);
    return $assignmentrecert->add_instance($data, true);
}

/**
 * delete an assignmentrecert instance
 * @param int $id
 * @return bool
 */
function assignrecert_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    $cm = get_coursemodule_from_instance('assignrecert', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $assignmentrecert = new assignrecert($context, null, null);
    return $assignmentrecert->delete_instance();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all assignmentrecert submissions and feedbacks in the database
 * and clean up any related data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array
 */
function assignrecert_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $status = array();
    $params = array('courseid'=>$data->courseid);
    $sql = "SELECT a.id FROM {assignrecert} a WHERE a.course=:courseid";
    $course = $DB->get_record('course', array('id'=>$data->courseid), '*', MUST_EXIST);
    if ($assigns = $DB->get_records_sql($sql, $params)) {
        foreach ($assigns as $assignrecert) {
            $cm = get_coursemodule_from_instance('assignrecert',
                                                 $assignrecert->id,
                                                 $data->courseid,
                                                 false,
                                                 MUST_EXIST);
            $context = context_module::instance($cm->id);
            $assignmentrecert = new assignrecert($context, $cm, $course);
            $status = array_merge($status, $assignmentrecert->reset_userdata($data));
        }
    }
    return $status;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every assignmentrecert event in the site is checked, else
 * only assignmentrecert events belonging to the course specified are checked.
 *
 * @param int $courseid
 * @return bool
 */
function assignrecert_refresh_events($courseid = 0) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    if ($courseid) {
        // Make sure that the course id is numeric.
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$assigns = $DB->get_records('assignrecert', array('course' => $courseid))) {
            return false;
        }
        // Get course from courseid parameter.
        if (!$course = $DB->get_record('course', array('id' => $courseid), '*')) {
            return false;
        }
    } else {
        if (!$assigns = $DB->get_records('assignrecert')) {
            return false;
        }
    }
    foreach ($assigns as $assignrecert) {
        // Get course and course module for the assignmentrecert.
        list($course, $cm) = get_course_and_cm_from_instance($assignrecert->id, 'assignrecert', $assignrecert->course);

        // Refresh the assignmentrecert's calendar events.
        $context = context_module::instance($cm->id);
        $assignmentrecert = new assignrecert($context, $cm, $course);
        $assignmentrecert->update_calendar($cm->id);
    }

    return true;
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid The ID of the course to reset
 * @param string $type Optional type of assignmentrecert to limit the reset to a particular assignmentrecert type
 */
function assignrecert_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $params = array('moduletype'=>'assignrecert', 'courseid'=>$courseid);
    $sql = 'SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
            FROM {assignrecert} a, {course_modules} cm, {modules} m
            WHERE m.name=:moduletype AND m.id=cm.module AND cm.instance=a.id AND a.course=:courseid';

    if ($assignmentrecerts = $DB->get_records_sql($sql, $params)) {
        foreach ($assignmentrecerts as $assignmentrecert) {
            assignrecert_grade_item_update($assignmentrecert, 'reset');
        }
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the assignmentrecert.
 * @param moodleform $mform form passed by reference
 */
function assignrecert_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'assignheader', get_string('modulenameplural', 'assignrecert'));
    $name = get_string('deleteallsubmissions', 'assignrecert');
    $mform->addElement('advcheckbox', 'reset_assignrecert_submissions', $name);
    $mform->addElement('advcheckbox', 'reset_assignrecert_user_overrides',
        get_string('removealluseroverrides', 'assignrecert'));
    $mform->addElement('advcheckbox', 'reset_assignrecert_group_overrides',
        get_string('removeallgroupoverrides', 'assignrecert'));
}

/**
 * Course reset form defaults.
 * @param  object $course
 * @return array
 */
function assignrecert_reset_course_form_defaults($course) {
    return array('reset_assignrecert_submissions' => 1,
            'reset_assignrecert_group_overrides' => 1,
            'reset_assignrecert_user_overrides' => 1);
}

/**
 * Update an assignmentrecert instance
 *
 * This is done by calling the update_instance() method of the assignmentrecert type class
 * @param stdClass $data
 * @param stdClass $form - unused
 * @return object
 */
function assignrecert_update_instance(stdClass $data, $form) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    $context = context_module::instance($data->coursemodule);
    $assignmentrecert = new assignrecert($context, null, null);
    return $assignmentrecert->update_instance($data);
}

/**
 * This function updates the events associated to the assignrecert.
 * If $override is non-zero, then it updates only the events
 * associated with the specified override.
 *
 * @uses ASSIGNRECERT_MAX_EVENT_LENGTH
 * @param assignrecert $assignrecert the assignrecert object.
 * @param object $override (optional) limit to a specific override
 */
function assignrecert_update_events($assignrecert, $override = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $assignrecertinstance = $assignrecert->get_instance();

    // Load the old events relating to this assignrecert.
    $conds = array('modulename' => 'assignrecert', 'instance' => $assignrecertinstance->id);
    if (!empty($override)) {
        // Only load events for this override.
        if (isset($override->userid)) {
            $conds['userid'] = $override->userid;
        } else {
            $conds['groupid'] = $override->groupid;
        }
    }
    $oldevents = $DB->get_records('event', $conds);

    // Now make a todo list of all that needs to be updated.
    if (empty($override)) {
        // We are updating the primary settings for the assignrecert, so we need to add all the overrides.
        $overrides = $DB->get_records('assignrecert_overrides', array('assignrecertid' => $assignrecertinstance->id));
        // As well as the original assignrecert (empty override).
        $overrides[] = new stdClass();
    } else {
        // Just do the one override.
        $overrides = array($override);
    }

    if (!empty($assignrecert->get_course_module())) {
        $cmid = $assignrecert->get_course_module()->id;
    } else {
        $cmid = get_coursemodule_from_instance('assignrecert', $assignrecertinstance->id, $assignrecertinstance->course)->id;
    }

    foreach ($overrides as $current) {
        $groupid   = isset($current->groupid) ? $current->groupid : 0;
        $userid    = isset($current->userid) ? $current->userid : 0;
        $allowsubmissionsfromdate  = isset($current->allowsubmissionsfromdate
        ) ? $current->allowsubmissionsfromdate : $assignrecertinstance->allowsubmissionsfromdate;
        $duedate = isset($current->duedate) ? $current->duedate : $assignrecertinstance->duedate;

        // Only add open/close events for an override if they differ from the assignrecert default.
        $addopen  = empty($current->id) || !empty($current->allowsubmissionsfromdate);
        $addclose = empty($current->id) || !empty($current->duedate);

        $event = new stdClass();
        $event->description = format_module_intro('assignrecert', $assignrecertinstance, $cmid);
        // Events module won't show user events when the courseid is nonzero.
        $event->courseid    = ($userid) ? 0 : $assignrecertinstance->course;
        $event->groupid     = $groupid;
        $event->userid      = $userid;
        $event->modulename  = 'assignrecert';
        $event->instance    = $assignrecertinstance->id;
        $event->timestart   = $allowsubmissionsfromdate;
        $event->timeduration = max($duedate - $allowsubmissionsfromdate, 0);
        $event->visible     = instance_is_visible('assignrecert', $assignrecert);
        $event->eventtype   = 'open';

        // Determine the event name.
        if ($groupid) {
            $params = new stdClass();
            $params->assignrecert = $assignrecertinstance->name;
            $params->group = groups_get_group_name($groupid);
            if ($params->group === false) {
                // Group doesn't exist, just skip it.
                continue;
            }
            $eventname = get_string('overridegroupeventname', 'assignrecert', $params);
        } else if ($userid) {
            $params = new stdClass();
            $params->assignrecert = $assignrecertinstance->name;
            $eventname = get_string('overrideusereventname', 'assignrecert', $params);
        } else {
            $eventname = $assignrecertinstance->name;
        }
        if ($addopen or $addclose) {
            if ($duedate and $allowsubmissionsfromdate and $event->timeduration <= ASSIGNRECERT_MAX_EVENT_LENGTH) {
                // Single event for the whole assignrecert.
                if ($oldevent = array_shift($oldevents)) {
                    $event->id = $oldevent->id;
                } else {
                    unset($event->id);
                }
                $event->name = $eventname;
                // The method calendar_event::create will reuse a db record if the id field is set.
                calendar_event::create($event);
            } else {
                // Separate start and end events.
                $event->timeduration  = 0;
                if ($allowsubmissionsfromdate && $addopen) {
                    if ($oldevent = array_shift($oldevents)) {
                        $event->id = $oldevent->id;
                    } else {
                        unset($event->id);
                    }
                    $event->name = $eventname.' ('.get_string('open', 'assignrecert').')';
                    // The method calendar_event::create will reuse a db record if the id field is set.
                    calendar_event::create($event);
                }
                if ($duedate && $addclose) {
                    if ($oldevent = array_shift($oldevents)) {
                        $event->id = $oldevent->id;
                    } else {
                        unset($event->id);
                    }
                    $event->name      = $eventname.' ('.get_string('duedate', 'assignrecert').')';
                    $event->timestart = $duedate;
                    $event->eventtype = 'close';
                    calendar_event::create($event);
                }
            }
        }
    }

    // Delete any leftover events.
    foreach ($oldevents as $badevent) {
        $badevent = calendar_event::load($badevent);
        $badevent->delete();
    }
}

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function assignrecert_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_PLAGIARISM:
            return true;
        case FEATURE_COMMENT:
            return true;
        case FEATURE_ARCHIVE_COMPLETION:
            return true;

        default:
            return null;
    }
}

/**
 * Lists all gradable areas for the advanced grading methods gramework
 *
 * @return array('string'=>'string') An array with area names as keys and descriptions as values
 */
function assignrecert_grading_areas_list() {
    return array('submissions'=>get_string('submissions', 'assignrecert'));
}


/**
 * extend an assigment navigation settings
 *
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function assignrecert_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $navref->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $context = $cm->context;
    $course = $PAGE->course;

    if (!$course) {
        return;
    }

    if (has_capability('mod/assignrecert:manageoverrides', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/assignrecert/overrides.php', array('cmid' => $PAGE->cm->id));
        $node = navigation_node::create(get_string('groupoverrides', 'assignrecert'),
            new moodle_url($url, array('mode' => 'group')),
            navigation_node::TYPE_SETTING, null, 'mod_assignrecert_groupoverrides');
        $navref->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('useroverrides', 'assignrecert'),
            new moodle_url($url, array('mode' => 'user')),
            navigation_node::TYPE_SETTING, null, 'mod_assignrecert_useroverrides');
        $navref->add_node($node, $beforekey);
    }

    // Link to gradebook.
    if (has_capability('gradereport/grader:view', $cm->context) &&
            has_capability('moodle/grade:viewall', $cm->context)) {
        $link = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
        $linkname = get_string('viewgradebook', 'assignrecert');
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING);
    }

    // Link to download all submissions.
    if (has_any_capability(array('mod/assignrecert:grade', 'mod/assignrecert:viewgrades'), $context)) {
        $link = new moodle_url('/mod/assignrecert/view.php', array('id' => $cm->id, 'action'=>'grading'));
        $node = $navref->add(get_string('viewgrading', 'assignrecert'), $link, navigation_node::TYPE_SETTING);

        $link = new moodle_url('/mod/assignrecert/view.php', array('id' => $cm->id, 'action'=>'downloadall'));
        $node = $navref->add(get_string('downloadall', 'assignrecert'), $link, navigation_node::TYPE_SETTING);
    }

    if (has_capability('mod/assignrecert:revealidentities', $context)) {
        $dbparams = array('id'=>$cm->instance);
        $assignmentrecert = $DB->get_record('assignrecert', $dbparams, 'blindmarking, revealidentities');

        if ($assignmentrecert && $assignmentrecert->blindmarking && !$assignmentrecert->revealidentities) {
            $urlparams = array('id' => $cm->id, 'action'=>'revealidentities');
            $url = new moodle_url('/mod/assignrecert/view.php', $urlparams);
            $linkname = get_string('revealidentities', 'assignrecert');
            $node = $navref->add($linkname, $url, navigation_node::TYPE_SETTING);
        }
    }
}

/**
 * Add a get_coursemodule_info function in case any assignmentrecert type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function assignrecert_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    $dbparams = array('id'=>$coursemodule->instance);
    $fields = 'id, name, alwaysshowdescription, allowsubmissionsfromdate, intro, introformat';
    if (! $assignmentrecert = $DB->get_record('assignrecert', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $assignmentrecert->name;
    if ($coursemodule->showdescription) {
        if ($assignmentrecert->alwaysshowdescription || time() > $assignmentrecert->allowsubmissionsfromdate) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('assignrecert', $assignmentrecert, $coursemodule->id, false);
        }
    }
    return $result;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function assignrecert_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array(
        'mod-assignrecert-*' => get_string('page-mod-assignrecert-x', 'assignrecert'),
        'mod-assignrecert-view' => get_string('page-mod-assignrecert-view', 'assignrecert'),
    );
    return $modulepagetype;
}

/**
 * Print an overview of all assignmentrecerts
 * for the courses.
 *
 * @param mixed $courses The list of courses to print the overview for
 * @param array $htmlarray The array of html to return
 *
 * @return true
 */
function assignrecert_print_overview($courses, &$htmlarray) {
    global $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return true;
    }

    if (!$assignmentrecerts = get_all_instances_in_courses('assignrecert', $courses)) {
        return true;
    }

    $assignmentrecertids = array();

    // Do assignmentrecert_base::isopen() here without loading the whole thing for speed.
    foreach ($assignmentrecerts as $key => $assignmentrecert) {
        $time = time();
        $isopen = false;
        if ($assignmentrecert->duedate) {
            $duedate = false;
            if ($assignmentrecert->cutoffdate) {
                $duedate = $assignmentrecert->cutoffdate;
            }
            if ($duedate) {
                $isopen = ($assignmentrecert->allowsubmissionsfromdate <= $time && $time <= $duedate);
            } else {
                $isopen = ($assignmentrecert->allowsubmissionsfromdate <= $time);
            }
        }
        if ($isopen) {
            $assignmentrecertids[] = $assignmentrecert->id;
        }
    }

    if (empty($assignmentrecertids)) {
        // No assignmentrecerts to look at - we're done.
        return true;
    }

    // Definitely something to print, now include the constants we need.
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $strduedate = get_string('duedate', 'assignrecert');
    $strcutoffdate = get_string('nosubmissionsacceptedafter', 'assignrecert');
    $strnolatesubmissions = get_string('nolatesubmissions', 'assignrecert');
    $strduedateno = get_string('duedateno', 'assignrecert');
    $strassignment = get_string('modulename', 'assignrecert');

    // We do all possible database work here *outside* of the loop to ensure this scales.
    list($sqlassignmentrecertids, $assignmentidparams) = $DB->get_in_or_equal($assignmentrecertids);

    $mysubmissions = null;
    $unmarkedsubmissions = null;

    foreach ($assignmentrecerts as $assignmentrecert) {

        // Do not show assignmentrecerts that are not open.
        if (!in_array($assignmentrecert->id, $assignmentrecertids)) {
            continue;
        }

        $context = context_module::instance($assignmentrecert->coursemodule);

        // Does the submission status of the assignmentrecert require notification?
        if (has_capability('mod/assignrecert:submit', $context, null, false)) {
            // Does the submission status of the assignmentrecert require notification?
            $submitdetails = assignrecert_get_mysubmission_details_for_print_overview($mysubmissions, $sqlassignmentrecertids,
                    $assignmentidparams, $assignmentrecert);
        } else {
            $submitdetails = false;
        }

        if (has_capability('mod/assignrecert:grade', $context, null, false)) {
            // Does the grading status of the assignmentrecert require notification ?
            $gradedetails = assignrecert_get_grade_details_for_print_overview($unmarkedsubmissions, $sqlassignmentrecertids,
                    $assignmentidparams, $assignmentrecert, $context);
        } else {
            $gradedetails = false;
        }

        if (empty($submitdetails) && empty($gradedetails)) {
            // There is no need to display this assignmentrecert as there is nothing to notify.
            continue;
        }

        $dimmedclass = '';
        if (!$assignmentrecert->visible) {
            $dimmedclass = ' class="dimmed"';
        }
        $href = $CFG->wwwroot . '/mod/assignrecert/view.php?id=' . $assignmentrecert->coursemodule;
        $basestr = '<div class="assignrecert overview">' .
               '<div class="name">' .
               $strassignment . ': '.
               '<a ' . $dimmedclass .
                   'title="' . $strassignment . '" ' .
                   'href="' . $href . '">' .
               format_string($assignmentrecert->name) .
               '</a></div>';
        if ($assignmentrecert->duedate) {
            $userdate = userdate($assignmentrecert->duedate);
            $basestr .= '<div class="info">' . $strduedate . ': ' . $userdate . '</div>';
        } else {
            $basestr .= '<div class="info">' . $strduedateno . '</div>';
        }
        if ($assignmentrecert->cutoffdate) {
            if ($assignmentrecert->cutoffdate == $assignmentrecert->duedate) {
                $basestr .= '<div class="info">' . $strnolatesubmissions . '</div>';
            } else {
                $userdate = userdate($assignmentrecert->cutoffdate);
                $basestr .= '<div class="info">' . $strcutoffdate . ': ' . $userdate . '</div>';
            }
        }

        // Show only relevant information.
        if (!empty($submitdetails)) {
            $basestr .= $submitdetails;
        }

        if (!empty($gradedetails)) {
            $basestr .= $gradedetails;
        }
        $basestr .= '</div>';

        if (empty($htmlarray[$assignmentrecert->course]['assignrecert'])) {
            $htmlarray[$assignmentrecert->course]['assignrecert'] = $basestr;
        } else {
            $htmlarray[$assignmentrecert->course]['assignrecert'] .= $basestr;
        }
    }
    return true;
}

/**
 * This api generates html to be displayed to students in print overview section, related to their submission status of the given
 * assignmentrecert.
 *
 * @param array $mysubmissions list of submissions of current user indexed by assignmentrecert id.
 * @param string $sqlassignmentrecertids sql clause used to filter open assignmentrecerts.
 * @param array $assignmentidparams sql params used to filter open assignmentrecerts.
 * @param stdClass $assignmentrecert current assignmentrecert
 *
 * @return bool|string html to display , false if nothing needs to be displayed.
 * @throws coding_exception
 */
function assignrecert_get_mysubmission_details_for_print_overview(&$mysubmissions, $sqlassignmentrecertids, $assignmentidparams,
                                                            $assignmentrecert) {
    global $USER, $DB;

    if ($assignmentrecert->nosubmissions) {
        // Offline assignmentrecert. No need to display alerts for offline assignmentrecerts.
        return false;
    }

    $strnotsubmittedyet = get_string('notsubmittedyet', 'assignrecert');

    if (!isset($mysubmissions)) {

        // Get all user submissions, indexed by assignmentrecert id.
        $dbparams = array_merge(array($USER->id), $assignmentidparams, array($USER->id));
        $mysubmissions = $DB->get_records_sql('SELECT a.id AS assignmentrecert,
                                                      a.nosubmissions AS nosubmissions,
                                                      g.timemodified AS timemarked,
                                                      g.grader AS grader,
                                                      g.grade AS grade,
                                                      s.status AS status
                                                 FROM {assignrecert} a, {assignrecert_submission} s
                                            LEFT JOIN {assignrecert_grades} g ON
                                                      g.assignmentrecert = s.assignmentrecert AND
                                                      g.userid = ? AND
                                                      g.attemptnumber = s.attemptnumber
                                                WHERE a.id ' . $sqlassignmentrecertids . ' AND
                                                      s.latest = 1 AND
                                                      s.assignmentrecert = a.id AND
                                                      s.userid = ?', $dbparams);
    }

    $submitdetails = '';
    $submitdetails .= '<div class="details">';
    $submitdetails .= get_string('mysubmission', 'assignrecert');
    $submission = false;

    if (isset($mysubmissions[$assignmentrecert->id])) {
        $submission = $mysubmissions[$assignmentrecert->id];
    }

    if ($submission && $submission->status == ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED) {
        // A valid submission already exists, no need to notify students about this.
        return false;
    }

    // We need to show details only if a valid submission doesn't exist.
    if (!$submission ||
        !$submission->status ||
        $submission->status == ASSIGNRECERT_SUBMISSION_STATUS_DRAFT ||
        $submission->status == ASSIGNRECERT_SUBMISSION_STATUS_NEW
    ) {
        $submitdetails .= $strnotsubmittedyet;
    } else {
        $submitdetails .= get_string('submissionstatus_' . $submission->status, 'assignrecert');
    }
    if ($assignmentrecert->markingworkflow) {
        $workflowstate = $DB->get_field('assignrecert_user_flags', 'workflowstate', array('assignmentrecert' =>
                $assignmentrecert->id, 'userid' => $USER->id));
        if ($workflowstate) {
            $gradingstatus = 'markingworkflowstate' . $workflowstate;
        } else {
            $gradingstatus = 'markingworkflowstate' . ASSIGNRECERT_MARKING_WORKFLOW_STATE_NOTMARKED;
        }
    } else if (!empty($submission->grade) && $submission->grade !== null && $submission->grade >= 0) {
        $gradingstatus = ASSIGNRECERT_GRADING_STATUS_GRADED;
    } else {
        $gradingstatus = ASSIGNRECERT_GRADING_STATUS_NOT_GRADED;
    }
    $submitdetails .= ', ' . get_string($gradingstatus, 'assignrecert');
    $submitdetails .= '</div>';
    return $submitdetails;
}

/**
 * This api generates html to be displayed to teachers in print overview section, related to the grading status of the given
 * assignmentrecert's submissions.
 *
 * @param array $unmarkedsubmissions list of submissions of that are currently unmarked indexed by assignmentrecert id.
 * @param string $sqlassignmentrecertids sql clause used to filter open assignmentrecerts.
 * @param array $assignmentidparams sql params used to filter open assignmentrecerts.
 * @param stdClass $assignmentrecert current assignmentrecert
 * @param context $context context of the assignmentrecert.
 *
 * @return bool|string html to display , false if nothing needs to be displayed.
 * @throws coding_exception
 */
function assignrecert_get_grade_details_for_print_overview(&$unmarkedsubmissions, $sqlassignmentrecertids, $assignmentidparams,
                                                     $assignmentrecert, $context) {
    global $DB;
    if (!isset($unmarkedsubmissions)) {
        // Build up and array of unmarked submissions indexed by assignmentrecert id/ userid
        // for use where the user has grading rights on assignmentrecert.
        $dbparams = array_merge(array(ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED), $assignmentidparams);
        $rs = $DB->get_recordset_sql('SELECT s.assignmentrecert as assignmentrecert,
                                             s.userid as userid,
                                             s.id as id,
                                             s.status as status,
                                             g.timemodified as timegraded
                                        FROM {assignrecert_submission} s
                                   LEFT JOIN {assignrecert_grades} g ON
                                             s.userid = g.userid AND
                                             s.assignmentrecert = g.assignmentrecert AND
                                             g.attemptnumber = s.attemptnumber
                                   LEFT JOIN {assignrecert} a ON
                                             a.id = s.assignmentrecert
                                       WHERE
                                             ( g.timemodified is NULL OR
                                             s.timemodified >= g.timemodified OR
                                             g.grade IS NULL OR
                                             (g.grade = -1 AND
                                             a.grade < 0)) AND
                                             s.timemodified IS NOT NULL AND
                                             s.status = ? AND
                                             s.latest = 1 AND
                                             s.assignmentrecert ' . $sqlassignmentrecertids, $dbparams);

        $unmarkedsubmissions = array();
        foreach ($rs as $rd) {
            $unmarkedsubmissions[$rd->assignmentrecert][$rd->userid] = $rd->id;
        }
        $rs->close();
    }

    // Count how many people can submit.
    $submissions = 0;
    if ($students = get_enrolled_users($context, 'mod/assignrecert:view', 0, 'u.id')) {
        foreach ($students as $student) {
            if (isset($unmarkedsubmissions[$assignmentrecert->id][$student->id])) {
                $submissions++;
            }
        }
    }

    if ($submissions) {
        $urlparams = array('id' => $assignmentrecert->coursemodule, 'action' => 'grading');
        $url = new moodle_url('/mod/assignrecert/view.php', $urlparams);
        $gradedetails = '<div class="details">' .
                '<a href="' . $url . '">' .
                get_string('submissionsnotgraded', 'assignrecert', $submissions) .
                '</a></div>';
        return $gradedetails;
    } else {
        return false;
    }

}

/**
 * Returns all assignmentrecerts since a given time.
 *
 * @param array $activities The activity information is returned in this array
 * @param int $index The current index in the activities array
 * @param int $timestart The earliest activity to show
 * @param int $courseid Limit the search to this course
 * @param int $cmid The course module id
 * @param int $userid Optional user id
 * @param int $groupid Optional group id
 * @return void
 */
function assignrecert_get_recent_mod_activity(&$activities,
                                        &$index,
                                        $timestart,
                                        $courseid,
                                        $cmid,
                                        $userid=0,
                                        $groupid=0) {
    global $CFG, $COURSE, $USER, $DB;

    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->get_cm($cmid);
    $params = array();
    if ($userid) {
        $userselect = 'AND u.id = :userid';
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin   = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin   = '';
    }

    $params['cminstance'] = $cm->instance;
    $params['timestart'] = $timestart;
    $params['submitted'] = ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;

    $userfields = user_picture::fields('u', null, 'userid');

    if (!$submissions = $DB->get_records_sql('SELECT asb.id, asb.timemodified, ' .
                                                     $userfields .
                                             '  FROM {assignrecert_submission} asb
                                                JOIN {assignrecert} a ON a.id = asb.assignmentrecert
                                                JOIN {user} u ON u.id = asb.userid ' .
                                          $groupjoin .
                                            '  WHERE asb.timemodified > :timestart AND
                                                     asb.status = :submitted AND
                                                     a.id = :cminstance
                                                     ' . $userselect . ' ' . $groupselect .
                                            ' ORDER BY asb.timemodified ASC', $params)) {
         return;
    }

    $groupmode       = groups_get_activity_groupmode($cm, $course);
    $cmcontext      = context_module::instance($cm->id);
    $grader          = has_capability('moodle/grade:viewall', $cmcontext);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cmcontext);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cmcontext);


    $showrecentsubmissions = get_config('assignrecert', 'showrecentsubmissions');
    $show = array();
    foreach ($submissions as $submission) {
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }
        // The act of submitting of assignmentrecert may be considered private -
        // only graders will see it if specified.
        if (empty($showrecentsubmissions)) {
            if (!$grader) {
                continue;
            }
        }

        if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($cm->groupingid)) {
                continue;
            }
            $usersgroups =  groups_get_all_groups($course->id, $submission->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return;
    }

    if ($grader) {
        require_once($CFG->libdir.'/gradelib.php');
        $userids = array();
        foreach ($show as $id => $submission) {
            $userids[] = $submission->userid;
        }
        $grades = grade_get_grades($courseid, 'mod', 'assignrecert', $cm->instance, $userids);
    }

    $aname = format_string($cm->name, true);
    foreach ($show as $submission) {
        $activity = new stdClass();
        // Fields required to display.
        $activity->timestamp    = $submission->timemodified;
        $activity->text         = $cm->name;
        $activity->link         = (new moodle_url('/mod/assignrecert/view.php', ['id' => $cm->id]))->out();
        $activity->user         = new stdClass();
        $userfields = explode(',', user_picture::fields());
        foreach ($userfields as $userfield) {
            if ($userfield == 'id') {
                // Aliased in SQL above.
                $activity->user->{$userfield} = $submission->userid;
            } else {
                $activity->user->{$userfield} = $submission->{$userfield};
            }
        }
        $activity->user->fullname = fullname($submission, $viewfullnames);
        // Other fields.
        $activity->type         = 'assignrecert';
        $activity->cmid         = $cm->id;
        $activity->name         = $aname;
        $activity->sectionnum   = $cm->sectionnum;
        if ($grader) {
            $activity->grade = null;
            if (!empty($grades->items[0]->grades)) {
                $activity->grade = $grades->items[0]->grades[$submission->userid]->str_long_grade;
            }
        }

        $activities[$index++] = $activity;
    }

    return;
}

/**
 * Checks if a scale is being used by an assignmentrecert.
 *
 * This is used by the backup code to decide whether to back up a scale
 * @param int $assignmentrecertid
 * @param int $scaleid
 * @return boolean True if the scale is used by the assignmentrecert
 */
function assignrecert_scale_used($assignmentrecertid, $scaleid) {
    global $DB;

    $return = false;
    $rec = $DB->get_record('assignrecert', array('id'=>$assignmentrecertid, 'grade'=>-$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of assignmentrecert
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean True if the scale is used by any assignmentrecert
 */
function assignrecert_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('assignrecert', array('grade'=>-$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function assignrecert_get_view_actions() {
    return array('view submission', 'view feedback');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function assignrecert_get_post_actions() {
    return array('upload', 'submit', 'submit for grading');
}

/**
 * Call cron on the assignrecert module.
 */
function assignrecert_cron() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    assignrecert::cron();

    $plugins = core_component::get_plugin_list('assignrecertsubmission');

    foreach ($plugins as $name => $plugin) {
        $disabled = get_config('assignrecertsubmission_' . $name, 'disabled');
        if (!$disabled) {
            $class = 'assignrecert_submission_' . $name;
            require_once($CFG->dirroot . '/mod/assignrecert/submission/' . $name . '/locallib.php');
            $class::cron();
        }
    }
    $plugins = core_component::get_plugin_list('assignrecertfeedback');

    foreach ($plugins as $name => $plugin) {
        $disabled = get_config('assignrecertfeedback_' . $name, 'disabled');
        if (!$disabled) {
            $class = 'assignrecert_feedback_' . $name;
            require_once($CFG->dirroot . '/mod/assignrecert/feedback/' . $name . '/locallib.php');
            $class::cron();
        }
    }

    return true;
}

/**
 * Returns all other capabilities used by this module.
 * @return array Array of capability strings
 */
function assignrecert_get_extra_capabilities() {
    return ['gradereport/grader:view', 'moodle/grade:viewall'];
}

/**
 * Create grade item for given assignmentrecert.
 *
 * @param stdClass $assignrecert record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function assignrecert_grade_item_update($assignrecert, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($assignrecert->courseid)) {
        $assignrecert->courseid = $assignrecert->course;
    }

    $params = array('itemname'=>$assignrecert->name, 'idnumber'=>$assignrecert->cmidnumber);

    // Check if feedback plugin for gradebook is enabled, if yes then
    // gradetype = GRADE_TYPE_TEXT else GRADE_TYPE_NONE.
    $gradefeedbackenabled = false;

    if (isset($assignrecert->gradefeedbackenabled)) {
        $gradefeedbackenabled = $assignrecert->gradefeedbackenabled;
    } else if ($assignrecert->grade == 0) { // Grade feedback is needed only when grade == 0.
        require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
        $mod = get_coursemodule_from_instance('assignrecert', $assignrecert->id, $assignrecert->courseid);
        $cm = context_module::instance($mod->id);
        $assignmentrecert = new assignrecert($cm, null, null);
        $gradefeedbackenabled = $assignmentrecert->is_gradebook_feedback_enabled();
    }

    if ($assignrecert->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $assignrecert->grade;
        $params['grademin']  = 0;

    } else if ($assignrecert->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$assignrecert->grade;

    } else if ($gradefeedbackenabled) {
        // $assignrecert->grade == 0 and feedback enabled.
        $params['gradetype'] = GRADE_TYPE_TEXT;
    } else {
        // $assignrecert->grade == 0 and no feedback enabled.
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/assignrecert',
                        $assignrecert->courseid,
                        'mod',
                        'assignrecert',
                        $assignrecert->id,
                        0,
                        $grades,
                        $params);
}

/**
 * Return grade for given user or all users.
 *
 * @param stdClass $assignrecert record of assignrecert with an additional cmidnumber
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function assignrecert_get_user_grades($assignrecert, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $cm = get_coursemodule_from_instance('assignrecert', $assignrecert->id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $assignmentrecert = new assignrecert($context, null, null);
    $assignmentrecert->set_instance($assignrecert);
    return $assignmentrecert->get_user_grades_for_gradebook($userid);
}

/**
 * Update activity grades.
 *
 * @param stdClass $assignrecert database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 */
function assignrecert_update_grades($assignrecert, $userid=0, $nullifnone=true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if ($assignrecert->grade == 0) {
        assignrecert_grade_item_update($assignrecert);

    } else if ($grades = assignrecert_get_user_grades($assignrecert, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        assignrecert_grade_item_update($assignrecert, $grades);

    } else {
        assignrecert_grade_item_update($assignrecert);
    }
}

/**
 * List the file areas that can be browsed.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array
 */
function assignrecert_get_file_areas($course, $cm, $context) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $areas = array(ASSIGNRECERT_INTROATTACHMENT_FILEAREA => get_string('introattachments', 'mod_assignrecert'));

    $assignmentrecert = new assignrecert($context, $cm, $course);
    foreach ($assignmentrecert->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }
    foreach ($assignmentrecert->get_feedback_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }

    return $areas;
}

/**
 * File browsing support for assignrecert module.
 *
 * @param file_browser $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function assignrecert_get_file_info($browser,
                              $areas,
                              $course,
                              $cm,
                              $context,
                              $filearea,
                              $itemid,
                              $filepath,
                              $filename) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    $urlbase = $CFG->wwwroot.'/pluginfile.php';
    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;

    // Need to find where this belongs to.
    $assignmentrecert = new assignrecert($context, $cm, $course);
    if ($filearea === ASSIGNRECERT_INTROATTACHMENT_FILEAREA) {
        if (!has_capability('moodle/course:managefiles', $context)) {
            // Students can not peak here!
            return null;
        }
        if (!($storedfile = $fs->get_file($assignmentrecert->get_context()->id,
                                          'mod_assignrecert', $filearea, 0, $filepath, $filename))) {
            return null;
        }
        return new file_info_stored($browser,
                        $assignmentrecert->get_context(),
                        $storedfile,
                        $urlbase,
                        $filearea,
                        $itemid,
                        true,
                        true,
                        false);
    }

    $pluginowner = null;
    foreach ($assignmentrecert->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if (array_key_exists($filearea, $pluginareas)) {
                $pluginowner = $plugin;
                break;
            }
        }
    }
    if (!$pluginowner) {
        foreach ($assignmentrecert->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible()) {
                $pluginareas = $plugin->get_file_areas();

                if (array_key_exists($filearea, $pluginareas)) {
                    $pluginowner = $plugin;
                    break;
                }
            }
        }
    }

    if (!$pluginowner) {
        return null;
    }

    $result = $pluginowner->get_file_info($browser, $filearea, $itemid, $filepath, $filename);
    return $result;
}

/**
 * Prints the complete info about a user's interaction with an assignmentrecert.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $assignrecert the database assignrecert record
 *
 * This prints the submission summary and feedback summary for this student.
 */
function assignrecert_user_complete($course, $user, $coursemodule, $assignrecert) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $context = context_module::instance($coursemodule->id);

    $assignmentrecert = new assignrecert($context, $coursemodule, $course);

    echo $assignmentrecert->view_student_summary($user, false);
}

/**
 * Rescale all grades for this activity and push the new grades to the gradebook.
 *
 * @param stdClass $course Course db record
 * @param stdClass $cm Course module db record
 * @param float $oldmin
 * @param float $oldmax
 * @param float $newmin
 * @param float $newmax
 */
function assignrecert_rescale_activity_grades($course, $cm, $oldmin, $oldmax, $newmin, $newmax) {
    global $DB;

    if ($oldmax <= $oldmin) {
        // Grades cannot be scaled.
        return false;
    }
    $scale = ($newmax - $newmin) / ($oldmax - $oldmin);
    if (($newmax - $newmin) <= 1) {
        // We would lose too much precision, lets bail.
        return false;
    }

    $params = array(
        'p1' => $oldmin,
        'p2' => $scale,
        'p3' => $newmin,
        'a' => $cm->instance
    );

    // Only rescale grades that are greater than or equal to 0. Anything else is a special value.
    $sql = 'UPDATE {assignrecert_grades} set grade = (((grade - :p1) * :p2) + :p3) where assignmentrecert = :a and grade >= 0';
    $dbupdate = $DB->execute($sql, $params);
    if (!$dbupdate) {
        return false;
    }

    // Now re-push all grades to the gradebook.
    $dbparams = array('id' => $cm->instance);
    $assignrecert = $DB->get_record('assignrecert', $dbparams);
    $assignrecert->cmidnumber = $cm->idnumber;

    assignrecert_update_grades($assignrecert);

    return true;
}

/**
 * Print the grade information for the assignmentrecert for this user.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $assignmentrecert
 */
function assignrecert_user_outline($course, $user, $coursemodule, $assignmentrecert) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/grade/grading/lib.php');

    $gradinginfo = grade_get_grades($course->id,
                                        'mod',
                                        'assignrecert',
                                        $assignmentrecert->id,
                                        $user->id);

    $gradingitem = $gradinginfo->items[0];
    $gradebookgrade = $gradingitem->grades[$user->id];

    if (empty($gradebookgrade->str_long_grade)) {
        return null;
    }
    $result = new stdClass();
    $result->info = get_string('outlinegrade', 'assignrecert', $gradebookgrade->str_long_grade);
    $result->time = $gradebookgrade->dategraded;

    return $result;
}

/**
 * Obtains the specific requirements for completion.
 *
 * @param object $cm Course-module
 * @return array Requirements for completion
 */
function assignrecert_get_completion_requirements($cm) {
    global $DB;

    $assignrecert = $DB->get_record('assignrecert', array('id' => $cm->instance));

    $result = array();

    if ($assignrecert->completionsubmit) {
        $result[] = get_string('submission', 'assignrecert');
    }

    return $result;
}

/**
 * Obtains the completion progress.
 *
 * @param object $cm      Course-module
 * @param int    $userid  User ID
 * @return string The current status of completion for the user
 */
function assignrecert_get_completion_progress($cm, $userid) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $assignrecert = new assignrecert(null, $cm, $cm->course);

    $result = array();

    // If completion option is enabled, evaluate it and return true/false.
    if ($assignrecert->get_instance()->completionsubmit) {
        $submission = $DB->get_record('assignrecert_submission',
                array('assignmentrecert' => $assignrecert->get_instance()->id, 'userid' => $userid), '*', IGNORE_MISSING);
        if ($submission && ($submission->status == ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED)) {
            $result[] = get_string('submitted', 'assignrecert');
        }
    }

    return $result;
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in assignrecert settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function assignrecert_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    if (empty($userid)) {
        return false;
    }

    $result = $type;

    $assignrecert = new assignrecert(null, $cm, $course);
    $instance = $assignrecert->get_instance();

    // If completion option is enabled, evaluate it and return true/false.
    if ($instance->completionsubmit) {
        if ($instance->teamsubmission) {
            $submission = $assignrecert->get_group_submission($userid, 0, false);
        } else {
            $submission = $assignrecert->get_user_submission($userid, false);
        }
        $newstate = $submission && $submission->status == ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED;
        $result = completion_info::aggregate_completion_states($type, $result, $newstate);
    }

    // Totara: Check for passing grade.
    if ($instance->completionpass) {
        require_once($CFG->libdir . '/gradelib.php');
        $item = grade_item::fetch([
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'assignrecert',
            'iteminstance' => $cm->instance,
            'outcomeid' => null
        ]);
        if ($item) {
            $grades = grade_grade::fetch_users_grades($item, [$userid], false);
            if (!empty($grades[$userid])) {
                $newstate = $grades[$userid]->is_passed($item);
                $result = completion_info::aggregate_completion_states($type, $result, $newstate);
            }
        }
    }

    return $result;
}

/**
 * Serves intro attachment files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function assignrecert_pluginfile($course,
                $cm,
                context $context,
                $filearea,
                $args,
                $forcedownload,
                array $options=array()) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    if (!has_capability('mod/assignrecert:view', $context)) {
        return false;
    }

    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    $assignrecert = new assignrecert($context, $cm, $course);

    if ($filearea !== ASSIGNRECERT_INTROATTACHMENT_FILEAREA) {
        return false;
    }
    if (!$assignrecert->show_intro()) {
        return false;
    }

    $itemid = (int)array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/mod_assignrecert/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Serve the grading panel as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function mod_assignrecert_output_fragment_gradingpanel($args) {
    global $CFG;

    $context = $args['context'];

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');
    $assignrecert = new assignrecert($context, null, null);

    $userid = clean_param($args['userid'], PARAM_INT);
    $attemptnumber = clean_param($args['attemptnumber'], PARAM_INT);
    $formdata = array();
    if (!empty($args['jsonformdata'])) {
        $serialiseddata = json_decode($args['jsonformdata']);
        parse_str($serialiseddata, $formdata);
    }
    $viewargs = array(
        'userid' => $userid,
        'attemptnumber' => $attemptnumber,
        'formdata' => $formdata
    );

    return $assignrecert->view('gradingpanel', $viewargs);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function assignrecert_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $updates = new stdClass();
    $updates = course_check_module_updates_since($cm, $from, array(ASSIGNRECERT_INTROATTACHMENT_FILEAREA), $filter);

    // Check if there is a new submission by the user or new grades.
    $select = 'assignmentrecert = :id AND userid = :userid AND (timecreated > :since1 OR timemodified > :since2)';
    $params = array('id' => $cm->instance, 'userid' => $USER->id, 'since1' => $from, 'since2' => $from);
    $updates->submissions = (object) array('updated' => false);
    $submissions = $DB->get_records_select('assignrecert_submission', $select, $params, '', 'id');
    if (!empty($submissions)) {
        $updates->submissions->updated = true;
        $updates->submissions->itemids = array_keys($submissions);
    }

    $updates->grades = (object) array('updated' => false);
    $grades = $DB->get_records_select('assignrecert_grades', $select, $params, '', 'id');
    if (!empty($grades)) {
        $updates->grades->updated = true;
        $updates->grades->itemids = array_keys($grades);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/assignrecert:viewgrades', $cm->context)) {
        $params = array('id' => $cm->instance, 'since1' => $from, 'since2' => $from);
        $select = 'assignmentrecert = :id AND (timecreated > :since1 OR timemodified > :since2)';

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers, SQL_PARAMS_NAMED);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->usersubmissions = (object) array('updated' => false);
        $submissions = $DB->get_records_select('assignrecert_submission', $select, $params, '', 'id');
        if (!empty($submissions)) {
            $updates->usersubmissions->updated = true;
            $updates->usersubmissions->itemids = array_keys($submissions);
        }

        $updates->usergrades = (object) array('updated' => false);
        $grades = $DB->get_records_select('assignrecert_grades', $select, $params, '', 'id');
        if (!empty($grades)) {
            $updates->usergrades->updated = true;
            $updates->usergrades->itemids = array_keys($grades);
        }
    }

    return $updates;
}

/**
 * Archives user's assignmentrecerts for a course
 *
 * @internal This function should only be used by the course archiving API.
 *           It should never invalidate grades or activity completion state as these
 *           operations need to be performed in specific order and are done inside
 *           the archive_course_activities() function.
 *
 * @param int $userid
 * @param int $courseid
 * @param int $windowopens
 *
 * @return boolean
 */
function assignrecert_archive_completion($userid, $courseid, $windowopens = NULL) {
    global $CFG, $DB;

    // Required for assignrecert class.
    require_once($CFG->dirroot . '/mod/assignrecert/locallib.php');

    $sql = "SELECT s.id AS submissionid,
                    a.id AS assignrecertid
            FROM {assignrecert_submission} s
            JOIN {assignrecert} a ON a.id = s.assignmentrecert AND a.course = :courseid
            WHERE s.userid = :userid";
    $params = array('userid' => $userid, 'courseid' => $courseid);

    if ($submissions = $DB->get_records_sql($sql, $params)) {
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        // NOTE: grades are deleted automatically during archiving, no need to do it here.
        //       Caches invalidation also happens automatically during archiving.

        foreach ($submissions as $submission) {
            $cm = get_coursemodule_from_instance('assignrecert', $submission->assignrecertid, $course->id);
            $context = context_module::instance($cm->id);

            // Delete assignmentrecert files and assignmentrecert grade.
            $assignmentrecert = new assignrecert($context, $cm, $course);
            $assignmentrecert->delete_user_submission($userid);
            $assignmentrecert->unlock_submission($userid);
        }
    }

    return true;
}
