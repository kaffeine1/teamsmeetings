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
 * Ad-hoc task to sync attendance for a single meeting
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_teamsmeetings\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/teamsmeetings/lib.php');

/**
 * Ad-hoc task class that syncs one meeting's attendance data
 */
class sync_meeting_attendance extends \core\task\adhoc_task {

    /**
     * Get task name for administration screens
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncsinglemeetingtask', 'mod_teamsmeetings');
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;

        $debugmode = get_config('mod_teamsmeetings', 'debug_mode');

        $data = $this->get_custom_data();
        if (empty($data->meetingid)) {
            return;
        }

        // Fetch meeting record with course module id.
        $sql = "SELECT tm.*, cm.id AS cmid
                FROM {teamsmeetings} tm
                JOIN {course_modules} cm ON cm.instance = tm.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'teamsmeetings'
               WHERE tm.id = ?";
        $meeting = $DB->get_record_sql($sql, [$data->meetingid], MUST_EXIST);

        try {
            teamsmeetings_sync_meeting_attendance($meeting, true);
        } catch (\Exception $e) {
            $msg = teamsmeetings_extract_error_message($e);
            if ($debugmode) {
                mtrace("  - Error: " . $msg);
            }
            $meeting->last_sync = time();
            $meeting->sync_status = 'error';
            $DB->update_record('teamsmeetings', $meeting);
        }
    }
}
