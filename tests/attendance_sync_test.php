<?php
// PHPUnit tests for Teams Meetings attendance sync logic.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

class mod_teamsmeetings_attendance_testcase extends advanced_testcase {
    public function test_update_attendance_summary() {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_teamsmeetings');
        $meeting = $generator->create_instance(['course' => $course->id, 'name' => 'Test']);
        $user = $this->getDataGenerator()->create_user();

        $DB->insert_record('teamsmeetings_sessions', [
            'meetingid' => $meeting->id,
            'userid' => $user->id,
            'sessionid' => 's1',
            'join_time' => 100,
            'leave_time' => 160,
            'duration' => 60
        ]);
        $DB->insert_record('teamsmeetings_sessions', [
            'meetingid' => $meeting->id,
            'userid' => $user->id,
            'sessionid' => 's2',
            'join_time' => 200,
            'leave_time' => 260,
            'duration' => 60
        ]);

        teamsmeetings_update_attendance_summary($meeting->id, $user->id);

        $attendance = $DB->get_record('teamsmeetings_attendance', [
            'meetingid' => $meeting->id,
            'userid' => $user->id
        ], '*', MUST_EXIST);

        $this->assertEquals(100, $attendance->join_time);
        $this->assertEquals(260, $attendance->leave_time);
        $this->assertEquals(120, $attendance->duration);
    }
}
