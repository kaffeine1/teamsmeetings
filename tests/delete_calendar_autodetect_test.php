<?php
// Test that delete_meeting uses the calendar endpoint when the meeting ID
// resembles a calendar event identifier.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/bootstrap.php');

class mod_teamsmeetings_delete_calendar_autodetect_testcase extends advanced_testcase {
    public function test_calendar_delete_called_for_outlook_id() {
        $api = new delete_calendar_autodetect_mock();
        $api->delete_meeting('AQMk123', 'user@example.com');
        $this->assertTrue($api->calendar_called);
    }
}

class delete_calendar_autodetect_mock extends \mod_teamsmeetings\external\teams_api {
    public $calendar_called = false;

    public function __construct() {
        // Skip parent constructor.
    }

    protected function delete_meeting_via_calendar(string $eventid, string $upn) {
        $this->calendar_called = true;
        return true;
    }
}

