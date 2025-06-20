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
 * @copyright  2025 Michele Dipace
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
        $debugmode = get_config('mod_teamsmeetings', 'debug_mode');

        if ($debugmode) {
            mtrace('Starting Teams attendance sync...');
        }
        
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
        // TODO: schedule per-meeting ad-hoc tasks for improved scalability.
        
        if (empty($meetings)) {
            if ($debugmode) {
                mtrace('No meetings to sync.');
            }
            return;
        }
        
        foreach ($meetings as $meeting) {
            $task = new \mod_teamsmeetings\task\sync_meeting_attendance();
            $task->set_component('mod_teamsmeetings');
            $task->set_custom_data(['meetingid' => $meeting->id]);
            \core\task\manager::queue_adhoc_task($task, true);

            if ($debugmode) {
                mtrace("Queued sync for meeting: {$meeting->name} (ID: {$meeting->id})");
            }
        }
    }
}