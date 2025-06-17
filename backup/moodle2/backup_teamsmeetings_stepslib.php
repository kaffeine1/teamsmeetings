<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_final_task.class.php');

class backup_teamsmeetings_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $teamsmeetings = new backup_nested_element('teamsmeetings', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'meeting_id', 'join_url', 'organizer',
            'start_time', 'end_time', 'recurring', 'last_sync', 'sync_status',
            'timecreated', 'timemodified'
        ));

        $attendance = new backup_nested_element('attendance');
        $record = new backup_nested_element('record', array('id'), array(
            'meetingid', 'userid', 'join_time', 'leave_time', 'duration', 'sessionid'
        ));

        $sessions = new backup_nested_element('sessions');
        $session = new backup_nested_element('session', array('id'), array(
            'meetingid', 'userid', 'sessionid', 'join_time', 'leave_time', 'duration', 'ip_address'
        ));

        $unmatchedusers = new backup_nested_element('unmatched_users');
        $unmatched = new backup_nested_element('unmatched', array('id'), array(
            'meetingid', 'teams_email', 'teams_displayname', 'teams_id', 'join_time',
            'leave_time', 'duration', 'mapped_userid', 'timecreated'
        ));

        $teamsmeetings->add_child($attendance);
        $attendance->add_child($record);
        $teamsmeetings->add_child($sessions);
        $sessions->add_child($session);
        $teamsmeetings->add_child($unmatchedusers);
        $unmatchedusers->add_child($unmatched);

        $teamsmeetings->set_source_table('teamsmeetings', array('id' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $record->set_source_table('teamsmeetings_attendance', array('meetingid' => backup::VAR_PARENTID));
            $session->set_source_table('teamsmeetings_sessions', array('meetingid' => backup::VAR_PARENTID));
            $unmatched->set_source_table('teamsmeetings_unmatched_users', array('meetingid' => backup::VAR_PARENTID));
        }

        $record->annotate_ids('user', 'userid');
        $session->annotate_ids('user', 'userid');
        $unmatched->annotate_ids('user', 'mapped_userid');

        $teamsmeetings->annotate_files('mod_teamsmeetings', 'intro', null);

        return $this->prepare_activity_structure($teamsmeetings);
    }
}
