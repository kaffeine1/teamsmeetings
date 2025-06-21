<?php
// Test that delete_meeting uses the calendar endpoint when requested.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/bootstrap.php');

class mod_teamsmeetings_delete_calendar_testcase extends advanced_testcase {
    public function test_calendar_delete_called() {
        $api = new delete_calendar_mock();
        $api->delete_meeting('evt1', 'user@example.com', true);
        $this->assertTrue($api->calendar_called);
    }
}

class delete_calendar_mock extends \mod_teamsmeetings\external\teams_api {
    public $calendar_called = false;

    public function __construct() {
        // Skip parent constructor.
    }

    protected function delete_meeting_via_calendar(string $eventid, string $upn) {
        $this->calendar_called = true;
        return true;
    }
}
