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
 * Cache definitions for mod_teamsmeetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = array(
    // Cache for OAuth tokens
    'oauth_token' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 3300, // 55 minutes (tokens usually last 1 hour)
        'invalidationevents' => array('teamsmeetings_token_refresh')
    ),
    
    // Cache for meeting data from Teams
    'meeting_data' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 300, // 5 minutes
        'invalidationevents' => array('teamsmeetings_meeting_updated')
    ),
    
    // Cache for attendance reports
    'attendance_data' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 600, // 10 minutes
        'invalidationevents' => array('teamsmeetings_attendance_synced')
    )
);