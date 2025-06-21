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
 * Settings for mod_teamsmeetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/teamsmeetings/lib.php');

    // General settings heading
    $settings->add(new admin_setting_heading('teamsmeetings/generalheading',
        get_string('settings', 'teamsmeetings'),
        get_string('configintro', 'teamsmeetings')));

    // Microsoft Azure settings
    $settings->add(new admin_setting_heading('teamsmeetings/azureheading',
        get_string('azuresettings', 'teamsmeetings'),
        get_string('azuresettings_desc', 'teamsmeetings')));

    // Tenant ID
    $settings->add(new admin_setting_configtext('mod_teamsmeetings/tenant_id',
        get_string('tenant_id', 'teamsmeetings'),
        get_string('tenant_id_desc', 'teamsmeetings'),
        '',
        PARAM_TEXT));

    // Client ID
    $settings->add(new admin_setting_configtext('mod_teamsmeetings/client_id',
        get_string('client_id', 'teamsmeetings'),
        get_string('client_id_desc', 'teamsmeetings'),
        '',
        PARAM_TEXT));

    // Client Secret
    $settings->add(new admin_setting_configpasswordunmask('mod_teamsmeetings/client_secret',
        get_string('client_secret', 'teamsmeetings'),
        get_string('client_secret_desc', 'teamsmeetings'),
        ''));

    // Test connection button.
    $button = html_writer::link(
        new moodle_url('/mod/teamsmeetings/test_connection.php'),
        get_string('testconnection', 'teamsmeetings'),
        ['class' => 'btn btn-secondary']
    );
    $settings->add(new admin_setting_heading('teamsmeetings/testconnection',
        get_string('testconnection', 'teamsmeetings'), $button));

    // Debug page button.
    $debugbutton = html_writer::link(
        new moodle_url('/mod/teamsmeetings/debug_teams.php'),
        get_string('debugpage', 'teamsmeetings'),
        ['class' => 'btn btn-secondary']
    );
    $settings->add(new admin_setting_heading('teamsmeetings/debugpage',
        get_string('debugpage', 'teamsmeetings'), $debugbutton));

    // Default meeting settings
    $settings->add(new admin_setting_heading('teamsmeetings/defaultsheading',
        get_string('defaultmeetingsettings', 'teamsmeetings'),
        get_string('defaultmeetingsettings_desc', 'teamsmeetings')));

    // Default auto admit setting
    $autoadmitoptions = array(
        'EveryoneInCompany' => get_string('everyoneincompany', 'teamsmeetings'),
        'Everyone' => get_string('everyone', 'teamsmeetings'),
        'EveryoneInCompanyExcludingGuests' => get_string('everyoneincompanyexcludingguests', 'teamsmeetings'),
        'OrganizerOnly' => get_string('organizeronly', 'teamsmeetings')
    );
    $settings->add(new admin_setting_configselect('mod_teamsmeetings/default_autoadmit',
        get_string('defaultautoadmit', 'teamsmeetings'),
        get_string('defaultautoadmit_desc', 'teamsmeetings'),
        'EveryoneInCompany',
        $autoadmitoptions));

    // Default recording setting
    $settings->add(new admin_setting_configcheckbox('mod_teamsmeetings/default_recording',
        get_string('defaultrecording', 'teamsmeetings'),
        get_string('defaultrecording_desc', 'teamsmeetings'),
        0));

    // Default attendance tracking
    $settings->add(new admin_setting_configcheckbox('mod_teamsmeetings/default_attendance',
        get_string('defaultattendance', 'teamsmeetings'),
        get_string('defaultattendance_desc', 'teamsmeetings'),
        1));

    // Minimum duration for attendance
    $settings->add(new admin_setting_configtext('mod_teamsmeetings/default_minduration',
        get_string('defaultminduration', 'teamsmeetings'),
        get_string('defaultminduration_desc', 'teamsmeetings'),
        10,
        PARAM_INT));

    // Cron settings
    $settings->add(new admin_setting_heading('teamsmeetings/cronheading',
        get_string('cronsettings', 'teamsmeetings'),
        get_string('cronsettings_desc', 'teamsmeetings')));

    // Sync frequency
    $syncoptions = array(
        300 => get_string('minutes5', 'teamsmeetings'),
        600 => get_string('minutes10', 'teamsmeetings'),
        900 => get_string('minutes15', 'teamsmeetings'),
        1800 => get_string('minutes30', 'teamsmeetings'),
        3600 => get_string('hour1', 'teamsmeetings')
    );
    $settings->add(new admin_setting_configselect('mod_teamsmeetings/sync_frequency',
        get_string('syncfrequency', 'teamsmeetings'),
        get_string('syncfrequency_desc', 'teamsmeetings'),
        300,
        $syncoptions));

    // Beta endpoint notice.
    global $OUTPUT;
    $betawarning = $OUTPUT->notification(
        get_string('betaendpointwarning', 'teamsmeetings'),
        \core\output\notification::NOTIFY_WARNING
    );
    $settings->add(new admin_setting_heading('teamsmeetings/betaendpointnotice',
        '',
        $betawarning));

    // Advanced settings
    $settings->add(new admin_setting_heading('teamsmeetings/advancedheading',
        get_string('advancedsettings', 'teamsmeetings'),
        get_string('advancedsettings_desc', 'teamsmeetings')));

    // Organizer UPN field
    $upnoptions = array(
        'email' => get_string('email'),
        'username' => get_string('username')
    );
    if ($fields = $DB->get_records('user_info_field', null, 'name', 'shortname, name')) {
        foreach ($fields as $field) {
            $upnoptions['profile_field_' . $field->shortname] = $field->name;
        }
    }
    $settings->add(new admin_setting_configselect('mod_teamsmeetings/upn_field',
        get_string('upnfield', 'teamsmeetings'),
        get_string('upnfield_desc', 'teamsmeetings'),
        'email',
        $upnoptions));

    // Debug mode
    $settings->add(new admin_setting_configcheckbox('mod_teamsmeetings/debug_mode',
        get_string('debugmode', 'teamsmeetings'),
        get_string('debugmode_desc', 'teamsmeetings'),
        0));

    // Use calendar fallback
    $settings->add(new admin_setting_configcheckbox('mod_teamsmeetings/use_calendar_fallback',
        get_string('use_calendar_fallback', 'teamsmeetings'),
        get_string('use_calendar_fallback_desc', 'teamsmeetings'),
        1));

    // Cache duration
    $settings->add(new admin_setting_configtext('mod_teamsmeetings/cache_duration',
        get_string('cacheduration', 'teamsmeetings'),
        get_string('cacheduration_desc', 'teamsmeetings'),
        3600,
        PARAM_INT));
}