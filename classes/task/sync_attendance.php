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
 * Scheduled task for syncing attendance data from Teams
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_teamsmeetings\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/teamsmeetings/lib.php');

/**
 * Sync attendance scheduled task
 */
class sync_attendance extends \core\task\scheduled_task {

    /**
     * Get name of the task
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncattendancetask', 'mod_teamsmeetings');
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        mtrace('Starting Teams attendance sync...');
        
        // Get all meetings that need sync
        // Meetings that ended in the last 7 days or are currently active
        $now = time();
        $sevendaysago = $now - (7 * 24 * 60 * 60);
        
        $sql = "SELECT tm.*, cm.id as cmid
                FROM {teamsmeetings} tm
                JOIN {course_modules} cm ON cm.instance = tm.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'teamsmeetings'
                WHERE tm.meeting_id IS NOT NULL
                  AND (
                    (tm.end_time = 0 AND tm.start_time < :now1) OR
                    (tm.end_time > :sevendaysago AND tm.end_time < :now2)
                  )
                  AND (tm.last_sync IS NULL OR tm.last_sync < :synctime)";
        
        $params = array(
            'now1' => $now,
            'sevendaysago' => $sevendaysago,
            'now2' => $now,
            'synctime' => $now - 300 // Don't sync more than once every 5 minutes
        );
        
        $meetings = $DB->get_records_sql($sql, $params, 0, 20); // Limit to 20 meetings
        
        if (empty($meetings)) {
            mtrace('No meetings to sync.');
            return;
        }
        
        $teams_api = new \mod_teamsmeetings\external\teams_api();
        $success = 0;
        $errors = 0;
        
        foreach ($meetings as $meeting) {
            mtrace("Syncing meeting: {$meeting->name} (ID: {$meeting->id})");
            
            try {
                // Get attendance data from Teams
                $attendance_data = $teams_api->get_attendance($meeting->meeting_id);
                
                if ($attendance_data === false) {
                    mtrace("  - No attendance data available yet");
                    continue;
                }
                
                // Process each participant
                $synced_users = 0;
                foreach ($attendance_data as $participant) {
                    if (isset($participant->userid)) {
                        // Clear existing sessions
                        $DB->delete_records('teamsmeetings_sessions', array(
                            'meetingid' => $meeting->id,
                            'userid' => $participant->userid
                        ));
                        
                        // Insert new sessions
                        if (isset($participant->sessions)) {
                            foreach ($participant->sessions as $session) {
                                $session_record = new \stdClass();
                                $session_record->meetingid = $meeting->id;
                                $session_record->userid = $participant->userid;
                                $session_record->sessionid = uniqid('teams_');
                                $session_record->join_time = $session->join_time;
                                $session_record->leave_time = $session->leave_time;
                                $session_record->duration = $session->duration;
                                
                                $DB->insert_record('teamsmeetings_sessions', $session_record);
                            }
                            
                            // Update attendance summary
                            teamsmeetings_update_attendance_summary($meeting->id, $participant->userid);
                            $synced_users++;
                        }
                    }
                }
                
                mtrace("  - Synced {$synced_users} users");
                
                // Update last sync time
                $meeting->last_sync = time();
                $meeting->sync_status = 'success';
                $DB->update_record('teamsmeetings', $meeting);
                
                $success++;
                
                // Log sync event
                $context = \context_module::instance($meeting->cmid);
                $event = \mod_teamsmeetings\event\attendance_synced::create(array(
                    'objectid' => $meeting->id,
                    'context' => $context,
                    'other' => array(
                        'synced' => $synced_users,
                        'automatic' => true
                    )
                ));
                $event->trigger();
                
            } catch (\Exception $e) {
                mtrace("  - Error: " . $e->getMessage());
                
                // Update sync status
                $meeting->last_sync = time();
                $meeting->sync_status = 'error';
                $DB->update_record('teamsmeetings', $meeting);
                
                $errors++;
            }
            
            // Sleep for 1 second to avoid rate limiting
            sleep(1);
        }
        
        mtrace("Teams attendance sync complete. Success: {$success}, Errors: {$errors}");
    }
}