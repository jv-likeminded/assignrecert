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
 * This file contains the definition for the library class for file submission plugin
 *
 * This class provides all the functionality for the new assignrecert module.
 *
 * @package assignrecertsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/eventslib.php');

defined('MOODLE_INTERNAL') || die();

// File areas for file submission assignmentrecert.
define('assignrecertsubMISSION_FILE_MAXSUMMARYFILES', 5);
define('assignrecertsubMISSION_FILE_FILEAREA', 'submission_files');

/**
 * Library class for file submission plugin extending submission plugin base class
 *
 * @package   assignrecertsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignrecert_submission_file extends assignrecert_submission_plugin {

    /**
     * Get the name of the file submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('file', 'assignrecertsubmission_file');
    }

    /**
     * Get file submission information from the database
     *
     * @param int $submissionid
     * @return mixed
     */
    private function get_file_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignrecertsubmission_file', array('submission'=>$submissionid));
    }

    /**
     * Get the default setting for file submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        if ($this->assignmentrecert->has_instance()) {
            $defaultmaxfilesubmissions = $this->get_config('maxfilesubmissions');
            $defaultmaxsubmissionsizebytes = $this->get_config('maxsubmissionsizebytes');
            $defaultfiletypes = $this->get_config('filetypeslist');
        } else {
            $defaultmaxfilesubmissions = get_config('assignrecertsubmission_file', 'maxfiles');
            $defaultmaxsubmissionsizebytes = get_config('assignrecertsubmission_file', 'maxbytes');
            $defaultfiletypes = get_config('assignrecertsubmission_file', 'filetypes');
        }
        $defaultfiletypes = (string)$defaultfiletypes;

        $settings = array();
        $options = array();
        for ($i = 1; $i <= get_config('assignrecertsubmission_file', 'maxfiles'); $i++) {
            $options[$i] = $i;
        }

        $name = get_string('maxfilessubmission', 'assignrecertsubmission_file');
        $mform->addElement('select', 'assignrecertsubmission_file_maxfiles', $name, $options);
        $mform->addHelpButton('assignrecertsubmission_file_maxfiles',
                              'maxfilessubmission',
                              'assignrecertsubmission_file');
        $mform->setDefault('assignrecertsubmission_file_maxfiles', $defaultmaxfilesubmissions);
        $mform->disabledIf('assignrecertsubmission_file_maxfiles', 'assignrecertsubmission_file_enabled', 'notchecked');

        $choices = get_max_upload_sizes($CFG->maxbytes,
                                        $COURSE->maxbytes,
                                        get_config('assignrecertsubmission_file', 'maxbytes'));

        $settings[] = array('type' => 'select',
                            'name' => 'maxsubmissionsizebytes',
                            'description' => get_string('maximumsubmissionsize', 'assignrecertsubmission_file'),
                            'options'=> $choices,
                            'default'=> $defaultmaxsubmissionsizebytes);

        $name = get_string('maximumsubmissionsize', 'assignrecertsubmission_file');
        $mform->addElement('select', 'assignrecertsubmission_file_maxsizebytes', $name, $choices);
        $mform->addHelpButton('assignrecertsubmission_file_maxsizebytes',
                              'maximumsubmissionsize',
                              'assignrecertsubmission_file');
        $mform->setDefault('assignrecertsubmission_file_maxsizebytes', $defaultmaxsubmissionsizebytes);
        $mform->disabledIf('assignrecertsubmission_file_maxsizebytes',
                           'assignrecertsubmission_file_enabled',
                           'notchecked');

        $name = get_string('acceptedfiletypes', 'assignrecertsubmission_file');
        $mform->addElement('filetypes', 'assignrecertsubmission_file_filetypes', $name);
        $mform->addHelpButton('assignrecertsubmission_file_filetypes', 'acceptedfiletypes', 'assignrecertsubmission_file');
        $mform->setDefault('assignrecertsubmission_file_filetypes', $defaultfiletypes);
        $mform->disabledIf('assignrecertsubmission_file_filetypes', 'assignrecertsubmission_file_enabled', 'notchecked');
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxfilesubmissions', $data->assignrecertsubmission_file_maxfiles);
        $this->set_config('maxsubmissionsizebytes', $data->assignrecertsubmission_file_maxsizebytes);

        if (!empty($data->assignrecertsubmission_file_filetypes)) {
            $this->set_config('filetypeslist', $data->assignrecertsubmission_file_filetypes);
        } else {
            $this->set_config('filetypeslist', '');
        }

        return true;
    }

    /**
     * File format options
     *
     * @return array
     */
    private function get_file_options() {
        $fileoptions = array('subdirs' => 1,
                                'maxbytes' => $this->get_config('maxsubmissionsizebytes'),
                                'maxfiles' => $this->get_config('maxfilesubmissions'),
                                'accepted_types' => $this->get_configured_typesets(),
                                'return_types' => (FILE_INTERNAL));
        if ($fileoptions['maxbytes'] == 0) {
            // Use module default.
            $fileoptions['maxbytes'] = get_config('assignrecertsubmission_file', 'maxbytes');
        }
        return $fileoptions;
    }

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $OUTPUT;

        if ($this->get_config('maxfilesubmissions') <= 0) {
            return false;
        }

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;

        $data = file_prepare_standard_filemanager($data,
                                                  'files',
                                                  $fileoptions,
                                                  $this->assignmentrecert->get_context(),
                                                  'assignrecertsubmission_file',
                                                  assignrecertsubMISSION_FILE_FILEAREA,
                                                  $submissionid);
        $mform->addElement('filemanager', 'files_filemanager', $this->get_name(), null, $fileoptions);

        return true;
    }

    /**
     * Count the number of files
     *
     * @param int $submissionid
     * @param string $area
     * @return int
     */
    private function count_files($submissionid, $area) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignmentrecert->get_context()->id,
                                     'assignrecertsubmission_file',
                                     $area,
                                     $submissionid,
                                     'id',
                                     false);

        return count($files);
    }

    /**
     * Save the files and trigger plagiarism plugin, if enabled,
     * to scan the uploaded files via events trigger
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $fileoptions = $this->get_file_options();

        $data = file_postupdate_standard_filemanager($data,
                                                     'files',
                                                     $fileoptions,
                                                     $this->assignmentrecert->get_context(),
                                                     'assignrecertsubmission_file',
                                                     assignrecertsubMISSION_FILE_FILEAREA,
                                                     $submission->id);

        $filesubmission = $this->get_file_submission($submission->id);

        // Plagiarism code event trigger when files are uploaded.

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignmentrecert->get_context()->id,
                                     'assignrecertsubmission_file',
                                     assignrecertsubMISSION_FILE_FILEAREA,
                                     $submission->id,
                                     'id',
                                     false);

        $count = $this->count_files($submission->id, assignrecertsubMISSION_FILE_FILEAREA);

        $params = array(
            'context' => context_module::instance($this->assignmentrecert->get_course_module()->id),
            'courseid' => $this->assignmentrecert->get_course()->id,
            'objectid' => $submission->id,
            'other' => array(
                'content' => '',
                'pathnamehashes' => array_keys($files)
            )
        );
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
            $params['relateduserid'] = $submission->userid;
        }
        $event = \assignrecertsubmission_file\event\assessable_uploaded::create($params);
        $event->set_legacy_files($files);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'filesubmissioncount' => $count,
            'groupid' => $groupid,
            'groupname' => $groupname
        );

        if ($filesubmission) {
            $filesubmission->numfiles = $this->count_files($submission->id,
                                                           assignrecertsubMISSION_FILE_FILEAREA);
            $updatestatus = $DB->update_record('assignrecertsubmission_file', $filesubmission);
            $params['objectid'] = $filesubmission->id;

            $event = \assignrecertsubmission_file\event\submission_updated::create($params);
            $event->set_assignrecert($this->assignmentrecert);
            $event->trigger();
            return $updatestatus;
        } else {
            $filesubmission = new stdClass();
            $filesubmission->numfiles = $this->count_files($submission->id,
                                                           assignrecertsubMISSION_FILE_FILEAREA);
            $filesubmission->submission = $submission->id;
            $filesubmission->assignmentrecert = $this->assignmentrecert->get_instance()->id;
            $filesubmission->id = $DB->insert_record('assignrecertsubmission_file', $filesubmission);
            $params['objectid'] = $filesubmission->id;

            $event = \assignrecertsubmission_file\event\submission_created::create($params);
            $event->set_assignrecert($this->assignmentrecert);
            $event->trigger();
            return $filesubmission->id > 0;
        }
    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @param stdClass $user The user record - unused
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignmentrecert->get_context()->id,
                                     'assignrecertsubmission_file',
                                     assignrecertsubMISSION_FILE_FILEAREA,
                                     $submission->id,
                                     'timemodified',
                                     false);

        foreach ($files as $file) {
            // Do we return the full folder path or just the file name?
            if (isset($submission->exportfullpath) && $submission->exportfullpath == false) {
                $result[$file->get_filename()] = $file;
            } else {
                $result[$file->get_filepath().$file->get_filename()] = $file;
            }
        }
        return $result;
    }

    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $count = $this->count_files($submission->id, assignrecertsubMISSION_FILE_FILEAREA);

        // Show we show a link to view all files for this plugin?
        $showviewlink = $count > assignrecertsubMISSION_FILE_MAXSUMMARYFILES;
        if ($count <= assignrecertsubMISSION_FILE_MAXSUMMARYFILES) {
            return $this->assignmentrecert->render_area_files('assignrecertsubmission_file',
                                                        assignrecertsubMISSION_FILE_FILEAREA,
                                                        $submission->id);
        } else {
            return get_string('countfiles', 'assignrecertsubmission_file', $count);
        }
    }

    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        return $this->assignmentrecert->render_area_files('assignrecertsubmission_file',
                                                    assignrecertsubMISSION_FILE_FILEAREA,
                                                    $submission->id);
    }

    /**
     * The assignmentrecert has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records('assignrecertsubmission_file',
                            array('assignmentrecert'=>$this->assignmentrecert->get_instance()->id));

        return true;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // Format the info for each submission plugin (will be added to log).
        $filecount = $this->count_files($submission->id, assignrecertsubMISSION_FILE_FILEAREA);

        return get_string('numfilesforlog', 'assignrecertsubmission_file', $filecount);
    }

    /**
     * Return true if there are no submission files
     * @param stdClass $submission
     */
    public function is_empty(stdClass $submission) {
        return $this->count_files($submission->id, assignrecertsubMISSION_FILE_FILEAREA) == 0;
    }

    /**
     * Determine if a submission is empty
     *
     * This is distinct from is_empty in that it is intended to be used to
     * determine if a submission made before saving is empty.
     *
     * @param stdClass $data The submission data
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        global $USER;
        $fs = get_file_storage();
        // Get a count of all the draft files, excluding any directories.
        $files = $fs->get_area_files(context_user::instance($USER->id)->id,
                                     'user',
                                     'draft',
                                     $data->files_filemanager,
                                     'id',
                                     false);
        return count($files) == 0;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(assignrecertsubMISSION_FILE_FILEAREA=>$this->get_name());
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy the files across.
        $contextid = $this->assignmentrecert->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid,
                                     'assignrecertsubmission_file',
                                     assignrecertsubMISSION_FILE_FILEAREA,
                                     $sourcesubmission->id,
                                     'id',
                                     false);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $destsubmission->id);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }

        // Copy the assignrecertsubmission_file record.
        if ($filesubmission = $this->get_file_submission($sourcesubmission->id)) {
            unset($filesubmission->id);
            $filesubmission->submission = $destsubmission->id;
            $DB->insert_record('assignrecertsubmission_file', $filesubmission);
        }
        return true;
    }

    /**
     * Return a description of external params suitable for uploading a file submission from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        return array(
            'files_filemanager' => new external_value(
                PARAM_INT,
                'The id of a draft area containing files for this submission.',
                VALUE_OPTIONAL
            )
        );
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external() {
        global $CFG;

        $configs = $this->get_config();

        // Get a size in bytes.
        if ($configs->maxsubmissionsizebytes == 0) {
            $configs->maxsubmissionsizebytes = get_max_upload_file_size($CFG->maxbytes, $this->assignmentrecert->get_course()->maxbytes,
                                                                        get_config('assignrecertsubmission_file', 'maxbytes'));
        }
        return (array) $configs;
    }

    /**
     * Get the type sets configured for this assignmentrecert.
     *
     * @return array('groupname', 'mime/type', ...)
     */
    private function get_configured_typesets() {
        $typeslist = (string)$this->get_config('filetypeslist');

        $util = new \core_form\filetypes_util();
        $sets = $util->normalize_file_types($typeslist);

        return $sets;
    }
}
