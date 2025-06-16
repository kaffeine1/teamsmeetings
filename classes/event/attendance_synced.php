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
 * Attendance synced event
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_teamsmeetings\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Attendance synced event class
 */
class attendance_synced extends \core\event\base {

    /**
     * Init method
     */
    protected function init() {
        $this->data['objecttable'] = 'teamsmeetings';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Return localised event name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventattendancesynced', 'mod_teamsmeetings');
    }

    /**
     * Returns description of what happened
     *
     * @return string
     */
    public function get_description() {
        $automatic = isset($this->other['automatic']) && $this->other['automatic'] ? 'automatically' : 'manually';
        return "The user with id '$this->userid' {$automatic} synced attendance for Teams meeting " .
               "with id '$this->objectid'. Synced: {$this->other['synced']} users.";
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/teamsmeetings/attendance.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->contextlevel)) {
            throw new \coding_exception('Context level must be set.');
        }

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
        
        if (!isset($this->other['synced'])) {
            throw new \coding_exception('The \'synced\' value must be set in other.');
        }
    }
}