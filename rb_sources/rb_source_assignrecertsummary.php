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

class rb_source_assignsummary extends rb_base_source {
    use \core_course\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $global_restriction_join = $this->get_global_report_restriction_join('asb', 'userid');

        $this->base = "(" .
        " SELECT a.id AS id," .
        " a.course AS assignmentrecert_course," .
        " a.name AS assignmentrecert_name," .
        " {$DB->sql_order_by_text('a.intro', '255')} AS assignmentrecert_intro," .
        " AVG(ag.grade) AS average_grade," .
        " SUM(ag.grade) AS sum_grade," .
        " MIN(ag.grade) AS min_grade," .
        " MAX(ag.grade) AS max_grade," .
        " MIN(asb.timemodified) AS min_timemodified," .
        " MAX(asb.timemodified) AS max_timemodified," .
        " MIN(ag.timemodified) AS min_timemarked," .
        " MAX(ag.timemodified) AS max_timemarked," .
        " a.grade AS assignmentrecert_maxgrade," .
        " COUNT(asb.userid) AS user_count" .
        " FROM {assignrecert_submission} asb" .
        " INNER JOIN {assignrecert} a ON asb.assignmentrecert = a.id" .
        " INNER JOIN {assignrecert_grades} ag ON ag.assignmentrecert = a.id AND ag.userid = asb.userid" .
        $global_restriction_join .
        " WHERE ag.grade > -1" . // Meaningful aggregations are only possible for numeric grade scales.
        " GROUP BY a.id, a.course, a.name, {$DB->sql_order_by_text('a.intro', '255')}, a.grade" .
        " )";
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->requiredcolumns = array();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_assignsummary');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_assignsummary');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_assignsummary');
        $this->usedcomponents[] = 'totara_cohort';

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
        $a = array();

        // Join courses and categories.
        $this->add_core_course_tables($a, 'base', 'assignmentrecert_course');
        $this->add_core_course_category_tables($a, 'course', 'category');

        return $a;
    }

    /**
     * Define column options
     * @return array
     */
    protected function define_columnoptions() {

        $columnoptions = array(
            // Assignment Recert name.
            new rb_column_option(
                'base',
                'name',
                get_string('assignmentname', 'rb_source_assignsummary'),
                'base.assignmentrecert_name',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),

            // Assignment Recert intro.
            new rb_column_option(
                'base',
                'intro',
                get_string('assignmentintro', 'rb_source_assignsummary'),
                'base.assignmentrecert_intro',
                array(
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'intro\'',
                        'component' => '\'mod_assignrecert\'',
                        'context' => '\'context_module\'',
                        'recordid' => 'base.id'
                    ),
                )
            ),

            // Assignment Recert maxgrade.
            new rb_column_option(
                'base',
                'maxgrade',
                get_string('assignmentmaxgrade', 'rb_source_assignsummary'),
                'base.assignmentrecert_maxgrade',
                array('displayfunc' => 'integer')
            ),

            // User count.
            new rb_column_option(
                'base',
                'user_count',
                get_string('usercount', 'rb_source_assignsummary'),
                'base.user_count',
                array('displayfunc' => 'integer')
            )
        );

        // Aggregate functions.
        $cols = array('average', 'sum', 'min', 'max');
        foreach ($cols as $col) {
            $columnoptions[] = new rb_column_option(
                'base',
                $col,
                get_string("{$col}grade", 'rb_source_assignsummary'),
                "base.{$col}_grade",
                array('displayfunc' => 'round')
            );
        }

        // MIN/MAX time modified.
        $cols = array('min', 'max');
        foreach ($cols as $col) {
            $columnoptions[] = new rb_column_option(
                'base',
                "{$col}_timemodified",
                get_string("{$col}lastmodified", 'rb_source_assignsummary'),
                "base.{$col}_timemodified",
                array('displayfunc' => 'nice_datetime')
            );
        }

        // MIN/MAX time marked.
        $cols = array('min', 'max');
        foreach ($cols as $col) {
            $columnoptions[] = new rb_column_option(
                'base',
                "{$col}_timemarked",
                get_string("{$col}lastmarked", 'rb_source_assignsummary'),
                "base.{$col}_timemarked",
                array('displayfunc' => 'nice_datetime')
            );
        }

        // Course and category fields.
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Define filter options
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        // assignmentrecert columns
        $cols = array('name', 'intro');
        foreach ($cols as $col) {
            $a[] = new rb_filter_option(
                'base',
                $col,
                get_string("assignmentrecert{$col}", 'rb_source_assignsummary'),
                'text'
            );
        }


        // min/max last modified
        $cols = array('min', 'max');
        foreach ($cols as $col) {
            $a[] = new rb_filter_option(
                'base',
                "{$col}_timemodified",
                get_string("{$col}lastmodified", 'rb_source_assignsummary'),
                'date'
            );
        }

        // min/max last marked
        $cols = array('min', 'max');
        foreach ($cols as $col) {
            $filteroptions[] = new rb_filter_option(
                'base',
                "{$col}_timemarked",
                get_string("{$col}lastmarked", 'rb_source_assignsummary'),
                'date'
            );
        }

        // Course and category filters.
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * Define default columns.
     * @return array
     */
    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'base',
                'value' => 'name'
            ),
            array(
                'type' => 'base',
                'value' => 'user_count'
            ),
            array(
                'type' => 'base',
                'value' => 'average'
            )
        );

        return $defaultcolumns;
    }

    /**
     * Display a number rounded to the nearest integer
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_roundgrade($field, $record, $isexport) {
        debugging('rb_source_assignsummary::rb_display_roundgrade has been deprecated since Totara 12.0, Use totara_reportbuilder\rb\display\round::display', DEBUG_DEVELOPER);
        return (integer)round($field);
    }
}
