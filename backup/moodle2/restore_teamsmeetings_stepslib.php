<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');

class restore_teamsmeetings_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];

        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('teamsmeetings', '/activity/teamsmeetings');

        if ($userinfo) {
            $paths[] = new restore_path_element('teamsmeetings_attendance', '/activity/teamsmeetings/attendance/record');
            $paths[] = new restore_path_element('teamsmeetings_session', '/activity/teamsmeetings/sessions/session');
            $paths[] = new restore_path_element('teamsmeetings_unmatched', '/activity/teamsmeetings/unmatched_users/unmatched');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_teamsmeetings($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('teamsmeetings', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_teamsmeetings_attendance($data) {
        global $DB;
        $data = (object)$data;
        $data->meetingid = $this->get_new_parentid('teamsmeetings');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('teamsmeetings_attendance', $data);
    }

    protected function process_teamsmeetings_session($data) {
        global $DB;
        $data = (object)$data;
        $data->meetingid = $this->get_new_parentid('teamsmeetings');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('teamsmeetings_sessions', $data);
    }

    protected function process_teamsmeetings_unmatched($data) {
        global $DB;
        $data = (object)$data;
        $data->meetingid = $this->get_new_parentid('teamsmeetings');
        if (!empty($data->mapped_userid)) {
            $data->mapped_userid = $this->get_mappingid('user', $data->mapped_userid);
        }
        $DB->insert_record('teamsmeetings_unmatched_users', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_teamsmeetings', 'intro', null);
    }
}
