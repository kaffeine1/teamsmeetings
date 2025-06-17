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
 * Privacy provider for mod_teamsmeetings.
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_teamsmeetings\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\context_aware_provider;
use context_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy API implementation for Teams Meetings module.
 */
class provider implements
        \core_privacy\local\metadata\provider,
        plugin_provider,
        context_aware_provider {

    /**
     * Describe the stored data.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table('teamsmeetings_attendance', [
            'meetingid'  => 'privacy:metadata:teamsmeetings_attendance:meetingid',
            'userid'     => 'privacy:metadata:teamsmeetings_attendance:userid',
            'join_time'  => 'privacy:metadata:teamsmeetings_attendance:join_time',
            'leave_time' => 'privacy:metadata:teamsmeetings_attendance:leave_time',
            'duration'   => 'privacy:metadata:teamsmeetings_attendance:duration',
            'sessionid'  => 'privacy:metadata:teamsmeetings_attendance:sessionid',
        ], 'privacy:metadata:teamsmeetings_attendance');

        $collection->add_database_table('teamsmeetings_sessions', [
            'meetingid'  => 'privacy:metadata:teamsmeetings_sessions:meetingid',
            'userid'     => 'privacy:metadata:teamsmeetings_sessions:userid',
            'sessionid'  => 'privacy:metadata:teamsmeetings_sessions:sessionid',
            'join_time'  => 'privacy:metadata:teamsmeetings_sessions:join_time',
            'leave_time' => 'privacy:metadata:teamsmeetings_sessions:leave_time',
            'duration'   => 'privacy:metadata:teamsmeetings_sessions:duration',
            'ip_address' => 'privacy:metadata:teamsmeetings_sessions:ip_address',
        ], 'privacy:metadata:teamsmeetings_sessions');

        $collection->add_database_table('teamsmeetings_unmatched_users', [
            'meetingid'        => 'privacy:metadata:teamsmeetings_unmatched_users:meetingid',
            'teams_email'      => 'privacy:metadata:teamsmeetings_unmatched_users:teams_email',
            'teams_displayname'=> 'privacy:metadata:teamsmeetings_unmatched_users:teams_displayname',
            'teams_id'         => 'privacy:metadata:teamsmeetings_unmatched_users:teams_id',
            'join_time'        => 'privacy:metadata:teamsmeetings_unmatched_users:join_time',
            'leave_time'       => 'privacy:metadata:teamsmeetings_unmatched_users:leave_time',
            'duration'         => 'privacy:metadata:teamsmeetings_unmatched_users:duration',
            'mapped_userid'    => 'privacy:metadata:teamsmeetings_unmatched_users:mapped_userid',
            'timecreated'      => 'privacy:metadata:teamsmeetings_unmatched_users:timecreated',
        ], 'privacy:metadata:teamsmeetings_unmatched_users');

        return $collection;
    }

    /**
     * Get list of contexts which contain user information for the specified user.
     *
     * @param int $userid The user ID to search.
     * @return contextlist The list of contexts containing user data.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'teamsmeetings'
                  JOIN {teamsmeetings} tm ON tm.id = cm.instance
                  JOIN {teamsmeetings_attendance} ta ON ta.meetingid = tm.id
                 WHERE c.contextlevel = :contextlevel AND ta.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'teamsmeetings'
                  JOIN {teamsmeetings} tm ON tm.id = cm.instance
                  JOIN {teamsmeetings_sessions} ts ON ts.meetingid = tm.id
                 WHERE c.contextlevel = :contextlevel AND ts.userid = :userid";
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'teamsmeetings'
                  JOIN {teamsmeetings} tm ON tm.id = cm.instance
                  JOIN {teamsmeetings_unmatched_users} tu ON tu.meetingid = tm.id
                 WHERE c.contextlevel = :contextlevel AND tu.mapped_userid = :userid";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the contexts approved.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('teamsmeetings', $context->instanceid);
            $meetingid = $cm->instance;

            $attendance = $DB->get_record('teamsmeetings_attendance', [
                'meetingid' => $meetingid,
                'userid' => $userid
            ]);
            if ($attendance) {
                writer::with_context($context)->export_data([
                    get_string('attendancereport', 'mod_teamsmeetings'),
                ], $attendance);
            }

            $sessions = $DB->get_records('teamsmeetings_sessions', [
                'meetingid' => $meetingid,
                'userid' => $userid
            ]);
            if ($sessions) {
                writer::with_context($context)->export_data([
                    get_string('sessions', 'mod_teamsmeetings'),
                ], (object)['sessions' => array_values($sessions)]);
            }

            $unmatched = $DB->get_records('teamsmeetings_unmatched_users', [
                'meetingid' => $meetingid,
                'mapped_userid' => $userid
            ]);
            if ($unmatched) {
                writer::with_context($context)->export_data([
                    get_string('unmatchedusers', 'mod_teamsmeetings'),
                ], (object)['records' => array_values($unmatched)]);
            }
        }
    }

    /**
     * Delete all user data within a single context.
     *
     * @param \context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('teamsmeetings', $context->instanceid);
        $meetingid = $cm->instance;

        $DB->delete_records('teamsmeetings_attendance', ['meetingid' => $meetingid]);
        $DB->delete_records('teamsmeetings_sessions', ['meetingid' => $meetingid]);
        $DB->delete_records('teamsmeetings_unmatched_users', ['meetingid' => $meetingid]);
    }

    /**
     * Delete all user data for the specified user across all contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('teamsmeetings', $context->instanceid);
            $meetingid = $cm->instance;

            $DB->delete_records('teamsmeetings_attendance', [
                'meetingid' => $meetingid,
                'userid' => $userid
            ]);
            $DB->delete_records('teamsmeetings_sessions', [
                'meetingid' => $meetingid,
                'userid' => $userid
            ]);
            $DB->delete_records('teamsmeetings_unmatched_users', [
                'meetingid' => $meetingid,
                'mapped_userid' => $userid
            ]);
        }
    }
}
