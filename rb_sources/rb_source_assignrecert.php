<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_assignrecert
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_assignrecert extends rb_base_source {
    use \core_course\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'auser');

        $this->base = '{assignrecert_submission}';
        $this->usedcomponents[] = 'mod_assignrecert';
        $this->usedcomponents[] = 'totara_cohort';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_assign');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_assign');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_assign');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * Define join list
     * @return array
     */
    protected function define_joinlist() {
        global $DB;
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'assignrecert']);

        $joinlist = array(
            // Join assignmentrecert.
            new rb_join(
                'assignrecert',
                'INNER',
                '{assignrecert}',
                'assignrecert.id = base.assignmentrecert',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),

            // Join assignmentrecert grade.
            new rb_join(
                'assignrecert_grades',
                'LEFT',
                '{assignrecert_grades}',
                'assignrecert.id = assignrecert_grades.assignmentrecert AND base.userid = assignrecert_grades.userid AND base.attemptnumber = assignrecert_grades.attemptnumber',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assignrecert'
            ),

            // Join grade_items.
            new rb_join(
                'grade_items',
                'INNER',
                '{grade_items}',
                'grade_items.courseid = assignrecert.course AND grade_items.itemmodule = \'assignrecert\' AND grade_items.iteminstance = assignrecert.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assignrecert'
            ),

            // Join grade_grades.
            new rb_join(
                'grade_grades',
                'LEFT',
                '{grade_grades}',
                'grade_grades.itemid = grade_items.id AND grade_grades.userid = base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'grade_items'
            ),

            // Join scale.
            new rb_join(
                'scale',
                'LEFT',
                '{scale}',
                'scale.id = grade_items.scaleid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'grade_items'
            ),

            // Join feedback comments.
            new rb_join(
                'assignrecert_comments',
                'LEFT',
                '{assignrecertfeedback_comments}',
                'assignrecert_comments.assignmentrecert = assignrecert.id AND assignrecert_comments.grade = assignrecert_grades.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                array('assignrecert', 'assignrecert_grades')
            ),

            // Join course modules for assignmentrecert name and link.
            new rb_join(
                'course_modules',
                'LEFT',
                '{course_modules}',
                "(course_modules.module = {$moduleid} AND course_modules.instance = assignrecert.id)",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assignrecert'
            ),
        );

        // join users, courses and categories
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'assignrecert', 'course');
        $this->add_core_course_category_tables($joinlist, 'course', 'category');

        return $joinlist;
    }

    /**
     * define column options
     * @return array
     */
    protected function define_columnoptions() {
        global $CFG;
        include_once($CFG->dirroot.'/mod/assignrecert/locallib.php');

        $columnoptions = array(
            // Assignment Recert name.
            new rb_column_option(
                'assignmentrecert',
                'name',
                get_string('assignmentname', 'rb_source_assign'),
                'assignrecert.name',
                array(
                    'joins' => 'assignrecert',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            ),

            // Assignment Recert name linked to the assignmentrecert activity.
            new rb_column_option(
                'assignmentrecert',
                'namelink',
                get_string('assignmentnamelink', 'rb_source_assign'),
                'assignrecert.name',
                array(
                    'joins' => ['assignrecert', 'course_modules'],
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'defaultheading' => get_string('assignmentname', 'rb_source_assign'),
                    'displayfunc' => 'assignrecert_name_link',
                    'extrafields' => array(
                        'id' => 'course_modules.id'
                    )
                )
            ),

            // Assignment Recert intro.
            new rb_column_option(
                'assignmentrecert',
                'intro',
                get_string('assignmentintro', 'rb_source_assign'),
                'assignrecert.intro',
                array(
                    'joins' => 'assignrecert',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'intro\'',
                        'component' => '\'mod_assignrecert\'',
                        'context' => '\'context_module\'',
                        'recordid' => 'assignrecert.id'
                    ),
                )
            ),

            // Assignment Recert status.
            new rb_column_option(
                'assignmentrecert',
                'status',
                get_string('submissionstatus', 'rb_source_assign'),
                "CASE WHEN assignrecert_grades.grade >= 0 THEN 'graded'
                      WHEN base.status = '" . ASSIGNRECERT_SUBMISSION_STATUS_SUBMITTED . "' THEN 'submitted'
                      WHEN base.status = '" . ASSIGNRECERT_SUBMISSION_STATUS_DRAFT . "' THEN 'draft'
                      ELSE 'notsubmitted' END",
                array(
                    'joins' => 'assignrecert_grades',
                    'displayfunc' => 'assignrecert_submission_status',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text'
                )
            ),

            // Grade scale values.
            new rb_column_option(
                'scale',
                'scale_values',
                get_string('gradescalevalues', 'rb_source_assign'),
                'scale.scale',
                array(
                    'displayfunc' => 'assignrecert_scale_values',
                    'joins' => 'scale'
                )
            ),

            // Submission grade.
            new rb_column_option(
                'base',
                'grade',
                get_string('submissiongrade', 'rb_source_assign'),
                'assignrecert_grades.grade',
                array(
                    'displayfunc' => 'assignrecert_submission_grade',
                    'joins' => array('assignrecert_grades', 'assignrecert'),
                    'extrafields' => array(
                        'scale_values' => 'scale.scale',
                        'assignrecert_grade' => 'assignrecert.grade'
                    )
                )
            ),

            // Feedback comment.
            new rb_column_option(
                'base',
                'comment',
                get_string('feedbackcomment', 'rb_source_assign'),
                'assignrecert_comments.commenttext',
                array(
                    'joins' => 'assignrecert_comments',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea'
                )
            ),

            // Submission last modified date.
            new rb_column_option(
                'base',
                'timemodified',
                get_string('lastmodifiedsubmission', 'rb_source_assign'),
                'base.timemodified',
                array(
                    'displayfunc' => 'nice_datetime'
                )
            ),

            // Grade last modified date.
            new rb_column_option(
                'base',
                'timemarked',
                get_string('lastmodifiedgrade', 'rb_source_assign'),
                'assignrecert_grades.timemodified',
                array(
                    'displayfunc' => 'nice_datetime',
                    'joins' => 'assignrecert_grades'
                )
            ),

            // Max grade.
            new rb_column_option(
                'grade_grades',
                'maxgrade',
                get_string('maxgrade', 'rb_source_assign'),
                'grade_grades.rawgrademax',
                array(
                    'displayfunc' => 'assignrecert_max_grade',
                    'joins' => array('grade_grades', 'assignrecert'),
                    'extrafields' => array(
                        'scale_values' => 'scale.scale',
                        'assignrecert_grade' => 'assignrecert.grade'
                    )
                )
            ),

            // Min grade.
            new rb_column_option(
                'grade_grades',
                'mingrade',
                get_string('mingrade', 'rb_source_assign'),
                'grade_grades.rawgrademin',
                array(
                    'displayfunc' => 'assignrecert_min_grade',
                    'joins' => array('grade_grades', 'assignrecert'),
                    'extrafields' => array(
                        'scale_values' => 'scale.scale',
                        'assignrecert_grade' => 'assignrecert.grade'
                    )
                )
            )
        );

        // User, course and category fields.
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * define filter options
     * @return array
     */
    protected function define_filteroptions() {

        $filteroptions = array(
            // Assignment Recert columns.
            new rb_filter_option(
                'assignmentrecert',
                'name',
                get_string('assignmentname', 'rb_source_assign'),
                'text'
            ),
            new rb_filter_option(
                'assignmentrecert',
                'intro',
                get_string('assignmentintro', 'rb_source_assign'),
                'text'
            ),

            // Submission status.
            new rb_filter_option(
                'assignmentrecert',
                'status',
                get_string('submissionstatus', 'rb_source_assign'),
                'select',
                array(
                    'selectchoices' => array(
                        'notsubmitted' => get_string('status_notsubmitted', 'rb_source_assign'),
                        'submitted' => get_string('status_submitted', 'rb_source_assign'),
                        'graded' => get_string('status_graded', 'rb_source_assign')),
                )
            ),

            // Submission grade.
            new rb_filter_option(
                'base',
                'grade',
                get_string('submissiongrade', 'rb_source_assign'),
                'number'
            ),

            // Last modified (submission).
            new rb_filter_option(
                'base',
                'timemodified',
                get_string('lastmodifiedsubmission', 'rb_source_assign'),
                'date'
            ),

            // Last modified (grade).
            new rb_filter_option(
                'base',
                'timemarked',
                get_string('lastmodifiedgrade', 'rb_source_assign'),
                'date'
            ),
        );

        // user, course and category filters
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * define required columns
     * @return array
     */
    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            // Scale id.
            new rb_column(
                'scale',
                'scaleid',
                '',
                'scale.id',
                array('hidden' => true, 'joins' => 'scale')
            ),
        );

        return $requiredcolumns;
    }

    /**
     * define default columns
     * @return array
     */
    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'assignmentrecert',
                'value' => 'name'
            ),
            array(
                'type' => 'user',
                'value' => 'fullname'
            ),
            array(
                'type' => 'base',
                'value' => 'grade'
            ),
        );
        return $defaultcolumns;
    }

    /**
     * Define default filters
     * @return array
     */
    protected function define_defaultfilters(){
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            )
        );

        return $defaultfilters;
    }

    /**
     * display the assignmentrecert type
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_assignmenttype($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_assignmenttype has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return get_string("type{$field}", 'assignmentrecert');
    }

    /**
     * display the scale values
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_scalevalues($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_scalevalues has been deprecated since Totara 12.0. Use mod_assignrecert\rb\display\assignrecert_scale_values::display', DEBUG_DEVELOPER);
        // If there's no scale values, return an empty string.
        if (empty($field)) {
            return '';
        }

        // If there are scale values, format them nicely.
        $v = explode(',', $field);
        $v = implode(', ', $v);
        return $v;
    }

    /**
     * Display the submission grade
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_submissiongrade($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_submissiongrade has been deprecated since Totara 12.0. Use mod_assignrecert\rb\display\assignrecert_submission_grade::display', DEBUG_DEVELOPER);
        // If there's no grade (yet), then return a string saying so.
        // If $field is 0, it is may be $mingrade or $grade.
        if ((integer)$field < 0 || empty($field)) {
            return get_string('nograde', 'rb_source_assign');
        }

        // If there's no scale values, return the raw grade.
        if (empty($record->scale_values)) {
            return (integer)$field;
        }

        // If there are scale values, work out which scale value was achieved.
        $v = explode(',', $record->scale_values);
        $i = (integer)$field - 1;
        return $v[$i];
    }

    /**
     * Display the max grade
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_maxgrade($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_maxgrade has been deprecated since Totara 12.0. Use mod_assignrecert\rb\display\assignrecert_max_grade::display', DEBUG_DEVELOPER);
        // if there's no scale values, return the raw grade.
        if (empty($record->scale_values)) {
            return (integer)$field;
        }

        // If there are scale values, work out which scale value is the maximum.
        $v = explode(',', $record->scale_values);
        $i = (integer)count($v) - 1;
        return $v[$i];
    }

    /**
     * Display the min grade
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_mingrade($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_mingrade has been deprecated since Totara 12.0. Use mod_assignrecert\rb\display\assignrecert_min_grade::display', DEBUG_DEVELOPER);
        // If there's no scale values, return the raw grade.
        if (empty($record->scale_values)) {
            return (integer)$field;
        }

        // If there are scale values, work out which scale value is the minimum.
        $v = explode(',', $record->scale_values);
        return $v[0];
    }
}
