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
 * Manually sync attendance data from Microsoft Teams.
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/teamsmeetings/lib.php');
require_once($CFG->dirroot . '/mod/teamsmeetings/classes/external/teams_api.php');

$cmid = required_param('id', PARAM_INT); // Course module ID.
require_sesskey();

$cm = get_coursemodule_from_id('teamsmeetings', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$teamsmeeting = $DB->get_record('teamsmeetings', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/teamsmeetings:syncattendance', $context);

$returnurl = new moodle_url('/mod/teamsmeetings/attendance.php', ['id' => $cm->id]);

if (empty($teamsmeeting->meeting_id)) {
    redirect($returnurl, get_string('nomeetingid', 'teamsmeetings'), null, 
        core\output\notification::NOTIFY_ERROR);
}

$teamsapi = new mod_teamsmeetings\external\teams_api();

try {
    $attendances = $teamsapi->get_attendance($teamsmeeting->meeting_id, $teamsmeeting->organizer);
    if ($attendances === false) {
        throw new moodle_exception('failedtosyncattendance', 'teamsmeetings');
    }

    $synced = 0;
    foreach ($attendances as $participant) {
        if (!isset($participant->userid)) {
            continue;
        }

        $DB->delete_records('teamsmeetings_sessions', [
            'meetingid' => $teamsmeeting->id,
            'userid' => $participant->userid
        ]);

        if (!empty($participant->sessions)) {
            foreach ($participant->sessions as $session) {
                $record = new stdClass();
                $record->meetingid = $teamsmeeting->id;
                $record->userid = $participant->userid;
                $record->sessionid = uniqid('teams_');
                $record->join_time = $session->join_time;
                $record->leave_time = $session->leave_time;
                $record->duration = $session->duration;
                $DB->insert_record('teamsmeetings_sessions', $record);
            }
            teamsmeetings_update_attendance_summary($teamsmeeting->id, $participant->userid);
            $synced++;
        }
    }

    $teamsmeeting->last_sync = time();
    $teamsmeeting->sync_status = 'success';
    $DB->update_record('teamsmeetings', $teamsmeeting);

    $event = mod_teamsmeetings\event\attendance_synced::create([
        'objectid' => $teamsmeeting->id,
        'context' => $context,
        'other' => [
            'synced' => $synced,
            'automatic' => false
        ]
    ]);
    $event->trigger();

    redirect($returnurl, get_string('synccomplete', 'teamsmeetings', $synced), 0, 
        core\output\notification::NOTIFY_SUCCESS);

} catch (Exception $e) {
    $teamsmeeting->last_sync = time();
    $teamsmeeting->sync_status = 'error';
    $DB->update_record('teamsmeetings', $teamsmeeting);

    debugging('Teams API sync error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    add_to_log($course->id, 'teamsmeetings', 'error', '', $e->getMessage(), $cm->id);

    redirect($returnurl, get_string('syncerror', 'teamsmeetings'), 0,
        core\output\notification::NOTIFY_ERROR);
}
