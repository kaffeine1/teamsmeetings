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
 * @copyright  2024 Michele Dipace
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
    global $DB;
    
    $data->timecreated = time();
    $data->timemodified = time();
    
    // Create Teams meeting via API
    $teams_api = new \mod_teamsmeetings\external\teams_api();
    $meeting_info = $teams_api->create_meeting($data);
    
    if ($meeting_info) {
        $data->meeting_id = $meeting_info->id;
        $data->join_url = $meeting_info->joinUrl;
    }
    
    $data->id = $DB->insert_record('teamsmeetings', $data);
    
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
    
    $data->timemodified = time();
    $data->id = $data->instance;
    
    // Update Teams meeting via API if needed
    if (isset($data->update_teams_meeting) && $data->update_teams_meeting) {
        $teams_api = new \mod_teamsmeetings\external\teams_api();
        $teams_api->update_meeting($data);
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
    
    if (!$meeting = $DB->get_record('teamsmeetings', array('id' => $id))) {
        return false;
    }
    
    // Delete Teams meeting via API
    if ($meeting->meeting_id) {
        $teams_api = new \mod_teamsmeetings\external\teams_api();
        $teams_api->delete_meeting($meeting->meeting_id);
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
 * Run cron tasks - versione corretta
 */
function teamsmeetings_cron() {
    global $DB;
    
    mtrace('Starting Teams attendance sync...');
    
    // Seleziona solo meeting che necessitano sincronizzazione
    $now = time();
    $twodaysago = $now - (2 * 24 * 60 * 60);
    
    // Meeting terminati nelle ultime 48 ore o attualmente in corso
    $sql = "SELECT * FROM {teamsmeetings} 
            WHERE meeting_id IS NOT NULL 
              AND ((end_time > :twodaysago AND end_time < :now) 
                   OR (start_time < :now2 AND (end_time = 0 OR end_time > :now3)))
            ORDER BY start_time DESC
            LIMIT 20"; // Limita per evitare sovraccarico
    
    $params = array(
        'twodaysago' => $twodaysago,
        'now' => $now,
        'now2' => $now,
        'now3' => $now
    );
    
    $meetings = $DB->get_records_sql($sql, $params);
    
    if (empty($meetings)) {
        mtrace('No meetings to sync.');
        return true;
    }
    
    $teams_api = new \mod_teamsmeetings\external\teams_api();
    $synced_meetings = 0;
    $errors = 0;
    
    foreach ($meetings as $meeting) {
        mtrace("Processing meeting: {$meeting->name} (ID: {$meeting->id})");
        
        try {
            $attendance_data = $teams_api->get_attendance($meeting->meeting_id);
            
            if ($attendance_data === false) {
                mtrace("  - No attendance data available");
                continue;
            }
            
            // Processa ogni partecipante
            $synced_users = 0;
            foreach ($attendance_data as $participant) {
                // Usa la funzione esistente per mappare utente Teams -> Moodle
                if (!isset($participant->userid)) {
                    mtrace("  - Skipping unmatched participant: {$participant->email}");
                    continue;
                }
                
                // Pulisci sessioni esistenti per evitare duplicati
                $DB->delete_records('teamsmeetings_sessions', array(
                    'meetingid' => $meeting->id,
                    'userid' => $participant->userid
                ));
                
                // Inserisci nuove sessioni
                if (isset($participant->sessions) && is_array($participant->sessions)) {
                    foreach ($participant->sessions as $session) {
                        $session_record = new stdClass();
                        $session_record->meetingid = $meeting->id;
                        $session_record->userid = $participant->userid;
                        $session_record->sessionid = uniqid('teams_');
                        $session_record->join_time = $session->join_time;
                        $session_record->leave_time = $session->leave_time;
                        $session_record->duration = $session->duration;
                        $session_record->ip_address = null; // Non disponibile da API
                        
                        $DB->insert_record('teamsmeetings_sessions', $session_record);
                    }
                    
                    // Aggiorna riepilogo presenze
                    teamsmeetings_update_attendance_summary($meeting->id, $participant->userid);
                    $synced_users++;
                }
            }
            
            mtrace("  - Synced {$synced_users} users");
            $synced_meetings++;
            
            // Segna il meeting come sincronizzato (aggiungi campo last_sync se necessario)
            $meeting->timemodified = time();
            $DB->update_record('teamsmeetings', $meeting);
            
        } catch (Exception $e) {
            mtrace("  - Error: " . $e->getMessage());
            $errors++;
        }
        
        // Pausa tra richieste per non sovraccaricare API
        sleep(1);
    }
    
    mtrace("Sync complete. Meetings: {$synced_meetings}, Errors: {$errors}");
    return true;
}