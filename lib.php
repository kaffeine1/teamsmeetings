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
 * Library functions for mod_teamsmeetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace (michele.dipace@kaffeine.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add teamsmeetings instance
 *
 * @param stdClass $data
 * @param mod_teamsmeetings_mod_form $mform
 * @return int new instance id
 */
function teamsmeetings_add_instance($data, $mform = null) {
    global $DB, $USER;

    $debugmode = get_config('mod_teamsmeetings', 'debug_mode');
    
    $data->timecreated = time();
    $data->timemodified = time();
    
    // Determine organizer UPN based on plugin setting.
    $upnfield = get_config('mod_teamsmeetings', 'upn_field');
    if ($upnfield === 'username') {
        $data->organizer = $USER->username;
    } else if (strpos($upnfield, 'profile_field_') === 0) {
        $pf = substr($upnfield, strlen('profile_field_'));
        $data->organizer = $USER->profile[$pf] ?? '';
    } else {
        $data->organizer = $USER->email;
    }
    $data->organizer = trim($data->organizer);
    if (!filter_var($data->organizer, FILTER_VALIDATE_EMAIL)) {
        throw new moodle_exception('invalidupn', 'mod_teamsmeetings');
    }

    // Debug resolved organizer.
    if ($debugmode) {
        debugging('Resolved organizer UPN: ' . $data->organizer, DEBUG_DEVELOPER);
    }

    // Debug data that will be sent to the Teams API.
    if ($debugmode) {
        debugging('Data passed to Teams API: ' . json_encode($data), DEBUG_DEVELOPER);
    }

    // Create Teams meeting via API
    $teams_api = new \mod_teamsmeetings\external\teams_api();
    try {
        $meeting_info = $teams_api->create_meeting($data, $data->organizer);

        if ($meeting_info) {
            if ($debugmode) {
                debugging('Teams API response object: ' . json_encode($meeting_info), DEBUG_DEVELOPER);
            }
            $data->meeting_id = $meeting_info->id;
            $data->join_url = $meeting_info->joinWebUrl;

            if (isset($meeting_info->creation_method)) {
                $data->creation_method = $meeting_info->creation_method;
                if ($meeting_info->creation_method === 'calendar') {
                    \core\notification::add(
                        get_string('meetingcreatedviacalendar', 'mod_teamsmeetings'),
                        \core\output\notification::NOTIFY_INFO
                    );
                } else {
                    \core\notification::add(
                        get_string('meetingcreatedviadirect', 'mod_teamsmeetings'),
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                }
            } else {
                $data->creation_method = 'direct';
            }
        }
    } catch (\moodle_exception $e) {
        $msg = teamsmeetings_extract_error_message($e);
        if ($debugmode) {
            debugging('Teams API create_meeting error: ' . $msg, DEBUG_DEVELOPER);
        }
        \core\notification::add(get_string('meetingcreationerror', 'teamsmeetings', $msg),
            \core\output\notification::NOTIFY_ERROR);
    }

    $data->id = $DB->insert_record('teamsmeetings', $data);

    // Debug values saved into the database.
    if ($debugmode) {
        debugging('Saved meeting record: ' . json_encode($data), DEBUG_DEVELOPER);
    }
    
    // Trigger event
    $event = \mod_teamsmeetings\event\meeting_created::create(array(
        'objectid' => $data->id,
        'context' => context_module::instance($data->coursemodule),
    ));
    $event->trigger();
    
    return $data->id;
}

/**
 * Update teamsmeetings instance
 *
 * @param stdClass $data
 * @param mod_teamsmeetings_mod_form $mform
 * @return bool
 */
function teamsmeetings_update_instance($data, $mform = null) {
    global $DB;

    $debugmode = get_config('mod_teamsmeetings', 'debug_mode');
    
    $data->timemodified = time();
    $data->id = $data->instance;
    
    // Preserve organizer and creation method
    $existing = $DB->get_record('teamsmeetings', ['id' => $data->id], 'organizer, creation_method');
    $data->organizer = $existing->organizer;
    $data->creation_method = $existing->creation_method;

    // Update Teams meeting via API.
    $teams_api = new \mod_teamsmeetings\external\teams_api();
    try {
        $viacalendar = ($data->creation_method === 'calendar');
        $teams_api->update_meeting($data, $data->organizer, $viacalendar);
    } catch (\moodle_exception $e) {
        $msg = teamsmeetings_extract_error_message($e);
        if ($debugmode) {
            debugging('Teams API update_meeting error: ' . $msg, DEBUG_DEVELOPER);
        }
        \core\notification::add(get_string('meetingupdateerror', 'teamsmeetings', $msg),
            \core\output\notification::NOTIFY_ERROR);
    }
    
    return $DB->update_record('teamsmeetings', $data);
}

/**
 * Delete teamsmeetings instance
 *
 * @param int $id
 * @return bool
 */
function teamsmeetings_delete_instance($id) {
    global $DB;

    $debugmode = get_config('mod_teamsmeetings', 'debug_mode');
    
    if (!$meeting = $DB->get_record('teamsmeetings', array('id' => $id))) {
        return false;
    }
    
    // Delete Teams meeting via API
    if ($meeting->meeting_id) {
        $teams_api = new \mod_teamsmeetings\external\teams_api();
        try {
            $viacalendar = ($meeting->creation_method === 'calendar');
            $teams_api->delete_meeting($meeting->meeting_id, $meeting->organizer, $viacalendar);
        } catch (\moodle_exception $e) {
            $msg = teamsmeetings_extract_error_message($e);
            if ($debugmode) {
                debugging('Teams API delete_meeting error: ' . $msg, DEBUG_DEVELOPER);
            }
            \core\notification::add(get_string('meetingdeleteerror', 'teamsmeetings', $msg),
                \core\output\notification::NOTIFY_ERROR);
        }
    }
    
    // Delete attendance records
    $DB->delete_records('teamsmeetings_attendance', array('meetingid' => $id));
    $DB->delete_records('teamsmeetings_sessions', array('meetingid' => $id));
    
    // Delete meeting record
    $DB->delete_records('teamsmeetings', array('id' => $id));
    
    return true;
}

/**
 * Return the list of view actions
 *
 * @return array
 */
function teamsmeetings_get_view_actions() {
    return array('view', 'view all', 'view attendance');
}

/**
 * Return the list of post actions
 *
 * @return array
 */
function teamsmeetings_get_post_actions() {
    return array('join meeting');
}

/**
 * Supports feature
 *
 * @param string $feature
 * @return mixed
 */
function teamsmeetings_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function teamsmeetings_reset_userdata($data) {
    global $DB;
    
    $status = array();
    
    if (!empty($data->reset_teamsmeetings_attendance)) {
        $meetingssql = "SELECT tm.id
                         FROM {teamsmeetings} tm
                        WHERE tm.course=?";
        $params = array($data->courseid);
        
        $DB->delete_records_select('teamsmeetings_attendance', "meetingid IN ($meetingssql)", $params);
        $DB->delete_records_select('teamsmeetings_sessions', "meetingid IN ($meetingssql)", $params);
        
        $status[] = array(
            'component' => get_string('modulenameplural', 'teamsmeetings'),
            'item' => get_string('attendancerecords', 'teamsmeetings'),
            'error' => false
        );
    }
    
    return $status;
}

/**
 * Log user attendance
 *
 * @param int $meetingid
 * @param int $userid
 * @param string $action 'join' or 'leave'
 * @param string $sessionid
 * @return bool
 */
function teamsmeetings_log_attendance($meetingid, $userid, $action, $sessionid = null) {
    global $DB;
    
    if ($action == 'join') {
        // Check if there's an existing open session
        $existing = $DB->get_record('teamsmeetings_sessions', array(
            'meetingid' => $meetingid,
            'userid' => $userid,
            'leave_time' => 0
        ));
        
        if (!$existing) {
            $session = new stdClass();
            $session->meetingid = $meetingid;
            $session->userid = $userid;
            $session->sessionid = $sessionid ?: uniqid('session_');
            $session->join_time = time();
            $session->ip_address = getremoteaddr();
            
            $DB->insert_record('teamsmeetings_sessions', $session);
        }
    } else if ($action == 'leave') {
        // Close the most recent open session
        $sql = "SELECT * FROM {teamsmeetings_sessions} 
                WHERE meetingid = ? AND userid = ? AND leave_time = 0 
                ORDER BY join_time DESC LIMIT 1";
        
        if ($session = $DB->get_record_sql($sql, array($meetingid, $userid))) {
            $session->leave_time = time();
            $session->duration = $session->leave_time - $session->join_time;
            $DB->update_record('teamsmeetings_sessions', $session);
            
            // Update or create attendance summary
            teamsmeetings_update_attendance_summary($meetingid, $userid);
        }
    }
    
    return true;
}

/**
 * Update attendance summary for a user
 *
 * @param int $meetingid
 * @param int $userid
 */
function teamsmeetings_update_attendance_summary($meetingid, $userid) {
    global $DB;
    
    // Calculate total attendance from all sessions
    $sql = "SELECT MIN(join_time) as first_join, 
                   MAX(leave_time) as last_leave,
                   SUM(duration) as total_duration
            FROM {teamsmeetings_sessions}
            WHERE meetingid = ? AND userid = ? AND leave_time > 0";
    
    $stats = $DB->get_record_sql($sql, array($meetingid, $userid));
    
    if ($stats && $stats->first_join) {
        if ($attendance = $DB->get_record('teamsmeetings_attendance', 
            array('meetingid' => $meetingid, 'userid' => $userid))) {
            // Update existing record
            $attendance->join_time = $stats->first_join;
            $attendance->leave_time = $stats->last_leave;
            $attendance->duration = $stats->total_duration;
            $DB->update_record('teamsmeetings_attendance', $attendance);
        } else {
            // Create new record
            $attendance = new stdClass();
            $attendance->meetingid = $meetingid;
            $attendance->userid = $userid;
            $attendance->join_time = $stats->first_join;
            $attendance->leave_time = $stats->last_leave;
            $attendance->duration = $stats->total_duration;
            $DB->insert_record('teamsmeetings_attendance', $attendance);
        }
    }
}

/**
 * Get attendance report for a meeting
 *
 * @param int $meetingid
 * @return array
 */
function teamsmeetings_get_attendance_report($meetingid) {
    global $DB;
    
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email,
                   ta.join_time, ta.leave_time, ta.duration,
                   COUNT(ts.id) as session_count
            FROM {user} u
            JOIN {teamsmeetings_attendance} ta ON ta.userid = u.id
            LEFT JOIN {teamsmeetings_sessions} ts ON ts.userid = u.id AND ts.meetingid = ta.meetingid
            WHERE ta.meetingid = ?
            GROUP BY u.id, u.firstname, u.lastname, u.email, ta.join_time, ta.leave_time, ta.duration
            ORDER BY u.lastname, u.firstname";
    
    return $DB->get_records_sql($sql, array($meetingid));
}

/**
 * Get detailed sessions for a user in a meeting
 *
 * @param int $meetingid
 * @param int $userid
 * @return array
 */
function teamsmeetings_get_user_sessions($meetingid, $userid) {
    global $DB;

    return $DB->get_records('teamsmeetings_sessions',
        array('meetingid' => $meetingid, 'userid' => $userid),
        'join_time ASC');
}

/**
 * Sync attendance for a single meeting from Microsoft Teams.
 *
 * @param stdClass $meeting  Meeting record with cmid property
 * @param bool $automatic    Whether the sync is triggered automatically
 */
function teamsmeetings_sync_meeting_attendance($meeting, $automatic = true) {
    global $DB;

    $debugmode = get_config('mod_teamsmeetings', 'debug_mode');

    if ($debugmode) {
        mtrace("Syncing meeting: {$meeting->name} (ID: {$meeting->id})");
    }

    $teams_api = new \mod_teamsmeetings\external\teams_api();

    $attendance_data = $teams_api->get_attendance($meeting->meeting_id, $meeting->organizer);
    if ($attendance_data === false) {
        if ($debugmode) {
            mtrace('  - No attendance data available yet');
        }
        return;
    }

    $synced_users = 0;
    foreach ($attendance_data as $participant) {
        if (isset($participant->userid)) {
            $DB->delete_records('teamsmeetings_sessions', [
                'meetingid' => $meeting->id,
                'userid' => $participant->userid
            ]);

            if (!empty($participant->sessions)) {
                foreach ($participant->sessions as $session) {
                    $session_record = new stdClass();
                    $session_record->meetingid = $meeting->id;
                    $session_record->userid = $participant->userid;
                    $session_record->sessionid = uniqid('teams_');
                    $session_record->join_time = $session->join_time;
                    $session_record->leave_time = $session->leave_time;
                    $session_record->duration = $session->duration;
                    $DB->insert_record('teamsmeetings_sessions', $session_record);
                }

                teamsmeetings_update_attendance_summary($meeting->id, $participant->userid);
                $synced_users++;
            }
        }
    }

    if ($debugmode) {
        mtrace("  - Synced {$synced_users} users");
    }

    $meeting->last_sync = time();
    $meeting->sync_status = 'success';
    $DB->update_record('teamsmeetings', $meeting);

    $context = \context_module::instance($meeting->cmid);
    $event = \mod_teamsmeetings\event\attendance_synced::create([
        'objectid' => $meeting->id,
        'context' => $context,
        'other' => [
            'synced' => $synced_users,
            'automatic' => $automatic
        ]
    ]);
    $event->trigger();
}

/**
 * Extract an error message from a Teams API exception.
 *
 * @param \moodle_exception $e
 * @return string
 */
function teamsmeetings_extract_error_message(\moodle_exception $e) {
    $candidates = [$e->getMessage()];
    if (property_exists($e, 'debuginfo') && !empty($e->debuginfo)) {
        $candidates[] = $e->debuginfo;
    }

    foreach ($candidates as $candidate) {
        $decoded = json_decode($candidate);
        if ($decoded && isset($decoded->error->message)) {
            return $decoded->error->message;
        }
        if (preg_match('/\{.*\}/', $candidate, $matches)) {
            $decoded = json_decode($matches[0]);
            if ($decoded && isset($decoded->error->message)) {
                return $decoded->error->message;
            }
        }
    }

    return $e->getMessage();
}
