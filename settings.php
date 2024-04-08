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
 * This file adds the settings pages to the navigation menu
 *
 * @package   mod_assignrecert
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/assignrecert/adminlib.php');

$ADMIN->add('modsettings', new admin_category('modassignrecertfolder', new lang_string('pluginname', 'mod_assignrecert'), $module->is_enabled() === false));

$settings = new admin_settingpage($section, get_string('settings', 'mod_assignrecert'), 'moodle/site:config', $module->is_enabled() === false);

if ($ADMIN->fulltree) {
    $menu = array();
    foreach (core_component::get_plugin_list('assignrecertfeedback') as $type => $notused) {
        $visible = !get_config('assignrecertfeedback_' . $type, 'disabled');
        if ($visible) {
            $menu['assignrecertfeedback_' . $type] = new lang_string('pluginname', 'assignrecertfeedback_' . $type);
        }
    }

    // The default here is feedback_comments (if it exists).
    $name = new lang_string('feedbackplugin', 'mod_assignrecert');
    $description = new lang_string('feedbackpluginforgradebook', 'mod_assignrecert');
    $settings->add(new admin_setting_configselect('assignrecert/feedback_plugin_for_gradebook',
                                                  $name,
                                                  $description,
                                                  'assignrecertfeedback_comments',
                                                  $menu));

    $name = new lang_string('showrecentsubmissions', 'mod_assignrecert');
    $description = new lang_string('configshowrecentsubmissions', 'mod_assignrecert');
    $settings->add(new admin_setting_configcheckbox('assignrecert/showrecentsubmissions',
                                                    $name,
                                                    $description,
                                                    0));

    $name = new lang_string('sendsubmissionreceipts', 'mod_assignrecert');
    $description = new lang_string('sendsubmissionreceipts_help', 'mod_assignrecert');
    $settings->add(new admin_setting_configcheckbox('assignrecert/submissionreceipts',
                                                    $name,
                                                    $description,
                                                    1));

    $name = new lang_string('submissionstatement', 'mod_assignrecert');
    $description = new lang_string('submissionstatement_help', 'mod_assignrecert');
    $default = get_string('submissionstatementdefault', 'mod_assignrecert');
    $setting = new admin_setting_configtextarea('assignrecert/submissionstatement',
                                                    $name,
                                                    $description,
                                                    $default);
    $setting->set_force_ltr(false);
    $settings->add($setting);

    $name = new lang_string('maxperpage', 'mod_assignrecert');
    $options = array(
        -1 => get_string('unlimitedpages', 'mod_assignrecert'),
        10 => 10,
        20 => 20,
        50 => 50,
        100 => 100,
    );
    $description = new lang_string('maxperpage_help', 'mod_assignrecert');
    $settings->add(new admin_setting_configselect('assignrecert/maxperpage',
                                                    $name,
                                                    $description,
                                                    -1,
                                                    $options));

    $name = new lang_string('defaultsettings', 'mod_assignrecert');
    $description = new lang_string('defaultsettings_help', 'mod_assignrecert');
    $settings->add(new admin_setting_heading('defaultsettings', $name, $description));

    $name = new lang_string('alwaysshowdescription', 'mod_assignrecert');
    $description = new lang_string('alwaysshowdescription_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/alwaysshowdescription',
                                                    $name,
                                                    $description,
                                                    1);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('allowsubmissionsfromdate', 'mod_assignrecert');
    $description = new lang_string('allowsubmissionsfromdate_help', 'mod_assignrecert');
    $setting = new admin_setting_configduration('assignrecert/allowsubmissionsfromdate',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('duedate', 'mod_assignrecert');
    $description = new lang_string('duedate_help', 'mod_assignrecert');
    $setting = new admin_setting_configduration('assignrecert/duedate',
                                                    $name,
                                                    $description,
                                                    604800);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('cutoffdate', 'mod_assignrecert');
    $description = new lang_string('cutoffdate_help', 'mod_assignrecert');
    $setting = new admin_setting_configduration('assignrecert/cutoffdate',
                                                    $name,
                                                    $description,
                                                    1209600);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('submissiondrafts', 'mod_assignrecert');
    $description = new lang_string('submissiondrafts_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/submissiondrafts',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('requiresubmissionstatement', 'mod_assignrecert');
    $description = new lang_string('requiresubmissionstatement_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/requiresubmissionstatement',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // Constants from "locallib.php".
    $options = array(
        'none' => get_string('attemptreopenmethod_none', 'mod_assignrecert'),
        'manual' => get_string('attemptreopenmethod_manual', 'mod_assignrecert'),
        'untilpass' => get_string('attemptreopenmethod_untilpass', 'mod_assignrecert')
    );
    $name = new lang_string('attemptreopenmethod', 'mod_assignrecert');
    $description = new lang_string('attemptreopenmethod_help', 'mod_assignrecert');
    $setting = new admin_setting_configselect('assignrecert/attemptreopenmethod',
                                                    $name,
                                                    $description,
                                                    'none',
                                                    $options);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // Constants from "locallib.php".
    $options = array(-1 => get_string('unlimitedattempts', 'mod_assignrecert'));
    $options += array_combine(range(1, 30), range(1, 30));
    $name = new lang_string('maxattempts', 'mod_assignrecert');
    $description = new lang_string('maxattempts_help', 'mod_assignrecert');
    $setting = new admin_setting_configselect('assignrecert/maxattempts',
                                                    $name,
                                                    $description,
                                                    -1,
                                                    $options);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('teamsubmission', 'mod_assignrecert');
    $description = new lang_string('teamsubmission_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/teamsubmission',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('preventsubmissionnotingroup', 'mod_assignrecert');
    $description = new lang_string('preventsubmissionnotingroup_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/preventsubmissionnotingroup',
        $name,
        $description,
        0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('requireallteammemberssubmit', 'mod_assignrecert');
    $description = new lang_string('requireallteammemberssubmit_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/requireallteammemberssubmit',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('teamsubmissiongroupingid', 'mod_assignrecert');
    $description = new lang_string('teamsubmissiongroupingid_help', 'mod_assignrecert');
    $setting = new admin_setting_configempty('assignrecert/teamsubmissiongroupingid',
                                                    $name,
                                                    $description);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendnotifications', 'mod_assignrecert');
    $description = new lang_string('sendnotifications_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/sendnotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendlatenotifications', 'mod_assignrecert');
    $description = new lang_string('sendlatenotifications_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/sendlatenotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendstudentnotificationsdefault', 'mod_assignrecert');
    $description = new lang_string('sendstudentnotificationsdefault_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/sendstudentnotifications',
                                                    $name,
                                                    $description,
                                                    1);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('blindmarking', 'mod_assignrecert');
    $description = new lang_string('blindmarking_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/blindmarking',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('markingworkflow', 'mod_assignrecert');
    $description = new lang_string('markingworkflow_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/markingworkflow',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('markingallocation', 'mod_assignrecert');
    $description = new lang_string('markingallocation_help', 'mod_assignrecert');
    $setting = new admin_setting_configcheckbox('assignrecert/markingallocation',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);
}

$ADMIN->add('modassignrecertfolder', $settings);
// Tell core we already added the settings structure.
$settings = null;

$ADMIN->add('modassignrecertfolder', new admin_category('assignrecertsubmissionplugins',
    new lang_string('submissionplugins', 'assignrecert'), !$module->is_enabled()));
$ADMIN->add('assignrecertsubmissionplugins', new assignrecert_admin_page_manage_assignrecert_plugins('assignrecertsubmission'));
$ADMIN->add('modassignrecertfolder', new admin_category('assignrecertfeedbackplugins',
    new lang_string('feedbackplugins', 'assignrecert'), !$module->is_enabled()));
$ADMIN->add('assignrecertfeedbackplugins', new assignrecert_admin_page_manage_assignrecert_plugins('assignrecertfeedback'));

foreach (core_plugin_manager::instance()->get_plugins_of_type('assignrecertsubmission') as $plugin) {
    /** @var \mod_assignrecert\plugininfo\assignrecertsubmission $plugin */
    $plugin->load_settings($ADMIN, 'assignrecertsubmissionplugins', $hassiteconfig);
}

foreach (core_plugin_manager::instance()->get_plugins_of_type('assignrecertfeedback') as $plugin) {
    /** @var \mod_assignrecert\plugininfo\assignrecertfeedback $plugin */
    $plugin->load_settings($ADMIN, 'assignrecertfeedbackplugins', $hassiteconfig);
}
