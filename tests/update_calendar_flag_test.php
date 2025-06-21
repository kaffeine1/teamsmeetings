<?php
// Test that update_meeting uses the calendar endpoint when explicit flag is true.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/bootstrap.php');

class mod_teamsmeetings_update_calendar_flag_testcase extends advanced_testcase {
    public function test_calendar_update_called_when_flag_true() {
        $api = new update_calendar_flag_mock();
        $data = new stdClass();
        $data->meeting_id = '123';
        $data->name = 'Test';
        $data->start_time = 1;
        $data->end_time = 2;
        $api->update_meeting($data, 'user@example.com', true);
        $this->assertTrue($api->calendar_called);
    }
}

class update_calendar_flag_mock extends \mod_teamsmeetings\external\teams_api {
    public $calendar_called = false;

    public function __construct() {
        // Skip parent constructor.
    }

    protected function update_meeting_via_calendar($data, $upn) {
        $this->calendar_called = true;
        return true;
    }
}

