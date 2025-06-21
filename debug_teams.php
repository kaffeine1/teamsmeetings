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
 * Debug page for Teams API integration.
 *
 * This page attempts to create a temporary meeting and outputs
 * detailed information to help diagnose configuration issues.
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/teamsmeetings/classes/external/teams_api.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/teamsmeetings/debug_teams.php');
$PAGE->set_title(get_string('debugpage', 'teamsmeetings'));
$PAGE->set_heading(get_string('debugpage', 'teamsmeetings'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('debugpage', 'teamsmeetings'));

// 1. Configuration check.
$tenantid = get_config('mod_teamsmeetings', 'tenant_id');
$clientid = get_config('mod_teamsmeetings', 'client_id');
$clientsecret = get_config('mod_teamsmeetings', 'client_secret');

echo html_writer::tag('h3', get_string('configcheck', 'teamsmeetings'));

$info = html_writer::start_div('alert alert-info');
$info .= '<strong>Tenant ID:</strong> '.($tenantid ? 'OK ✓' : 'MISSING ✗').'<br>';
$info .= '<strong>Client ID:</strong> '.($clientid ? 'OK ✓' : 'MISSING ✗').'<br>';
$info .= '<strong>Client Secret:</strong> '.($clientsecret ? 'OK ✓' : 'MISSING ✗').'<br>';
$info .= html_writer::end_div();

echo $info;

if (!$tenantid || !$clientid || !$clientsecret) {
    echo $OUTPUT->notification(get_string('configmissing', 'teamsmeetings'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

// 2. Determine organizer UPN.
$upnfield = get_config('mod_teamsmeetings', 'upn_field') ?: 'email';
echo html_writer::tag('h3', get_string('upncheck', 'teamsmeetings'));
echo '<strong>UPN field:</strong> '.s($upnfield).'<br>';

if ($upnfield === 'username') {
    $organizerupn = $USER->username;
} else if (strpos($upnfield, 'profile_field_') === 0) {
    $pf = substr($upnfield, strlen('profile_field_'));
    $organizerupn = $USER->profile[$pf] ?? '';
} else {
    $organizerupn = $USER->email;
}

echo '<strong>Resolved UPN:</strong> '.s($organizerupn).'<br>';

if (!filter_var($organizerupn, FILTER_VALIDATE_EMAIL)) {
    echo $OUTPUT->notification(get_string('invalidupn', 'teamsmeetings'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

// 3. Test API token and meeting creation.

echo html_writer::tag('h3', get_string('apitest', 'teamsmeetings'));

    try {
        $teamsapi = new \mod_teamsmeetings\external\teams_api();
        echo $OUTPUT->notification('Token obtained', \core\output\notification::NOTIFY_SUCCESS);

        $token = $teamsapi->get_access_token_debug();
        $curl = new curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        $userurl = \mod_teamsmeetings\external\teams_api::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '?$select=userPrincipalName,assignedPlans';
        $userresp = $curl->get($userurl);
        $userdata = json_decode($userresp);

        $skuurl = \mod_teamsmeetings\external\teams_api::GRAPH_API_URL . '/subscribedSkus';
        $skuresp = $curl->get($skuurl);
        $skuinfo = json_decode($skuresp);

        $hasteamsplan = false;
        if (!empty($userdata->assignedPlans)) {
            foreach ($userdata->assignedPlans as $plan) {
                if (!empty($plan->service) && preg_match('/teams/i', $plan->service)) {
                    $hasteamsplan = true;
                    break;
                }
            }
        }

        echo html_writer::tag('h3', get_string('licensecheck', 'teamsmeetings'));
        echo html_writer::div(get_string('teamsmeetingcapability', 'teamsmeetings') . ': ' .
            ($hasteamsplan ? get_string('yes') : get_string('no')), 'alert alert-info');

        echo html_writer::start_div();
        echo '<pre>' . s(json_encode($userdata, JSON_PRETTY_PRINT)) . '</pre>';
        echo '<pre>' . s(json_encode($skuinfo, JSON_PRETTY_PRINT)) . '</pre>';
        echo html_writer::end_div();

    $testmeeting = new stdClass();
    $testmeeting->name = 'Debug meeting - '.date('Y-m-d H:i:s');
    $testmeeting->intro = 'Temporary meeting for debugging';
    $testmeeting->start_time = time() + 3600;
    $testmeeting->end_time = time() + 7200;
    $testmeeting->auto_admit_users = 'EveryoneInCompany';
    $testmeeting->record_meeting = false;

    echo html_writer::start_div('alert alert-info');
    echo '<strong>Meeting data:</strong><br>';
    echo 'Name: '.$testmeeting->name.'<br>';
    echo 'Start: '.userdate($testmeeting->start_time).'<br>';
    echo 'End: '.userdate($testmeeting->end_time).'<br>';
    echo 'Organizer: '.s($organizerupn).'<br>';
    echo html_writer::end_div();

    echo html_writer::div('Creating meeting...', 'alert alert-warning');

    $meetinginfo = $teamsapi->create_meeting($testmeeting, $organizerupn);

    if ($meetinginfo && isset($meetinginfo->id)) {
        echo html_writer::start_div('alert alert-success');
        echo '<strong>Meeting created</strong><br>';
        echo 'ID: '.$meetinginfo->id.'<br>';
        echo 'Join URL: '.($meetinginfo->joinWebUrl ?? 'N/A').'<br>';
        echo html_writer::end_div();

        try {
            $viacalendar = isset($meetinginfo->creation_method) && $meetinginfo->creation_method === 'calendar';
            $teamsapi->delete_meeting($meetinginfo->id, $organizerupn, $viacalendar);
            echo html_writer::div('Test meeting removed.', 'alert alert-info');
        } catch (Exception $del) {
            echo html_writer::div('Could not delete test meeting: '.s($del->getMessage()), 'alert alert-warning');
        }
    } else {
        echo $OUTPUT->notification('Meeting creation failed', \core\output\notification::NOTIFY_ERROR);
    }

} catch (Exception $e) {
    echo html_writer::start_div('alert alert-danger');
    echo '<strong>Error:</strong><br>'.format_text($e->getMessage(), FORMAT_PLAIN).'<br>';
    if (method_exists($e, 'getCode')) {
        echo '<br><strong>Code:</strong> '.$e->getCode();
    }
    if (property_exists($e, 'debuginfo')) {
        echo '<br><strong>Debug info:</strong> '.format_text($e->debuginfo, FORMAT_PLAIN);
    }
    if (strpos($e->getMessage(), '{') !== false) {
        $jsonstart = strpos($e->getMessage(), '{');
        $jsonpart = substr($e->getMessage(), $jsonstart);
        $decoded = json_decode($jsonpart, true);
        if ($decoded) {
            echo '<pre>'.s(json_encode($decoded, JSON_PRETTY_PRINT)).'</pre>';
        }
    }
    echo html_writer::end_div();
}

$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'modsettingteamsmeetings']);
$testurl = new moodle_url('/mod/teamsmeetings/test_connection.php');

echo html_writer::start_tag('p');
echo html_writer::link($settingsurl, get_string('settings')); echo ' | ';
echo html_writer::link($testurl, get_string('testconnection', 'teamsmeetings'));
echo html_writer::end_tag('p');

echo $OUTPUT->footer();
