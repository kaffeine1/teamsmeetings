<?php
// Test that create_meeting falls back to calendar API when direct creation fails.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/bootstrap.php');

class mod_teamsmeetings_calendar_fallback_testcase extends advanced_testcase {
    public function test_calendar_fallback_used() {
        // Enable fallback.
        set_config('use_calendar_fallback', 1, 'mod_teamsmeetings');

        $api = new calendar_fallback_mock();

        $data = new stdClass();
        $data->name = 'Test';
        $data->start_time = time();

        $result = $api->create_meeting($data, 'organizer@example.com');

        $this->assertTrue($api->direct_called);
        $this->assertTrue($api->calendar_called);
        $this->assertEquals('calendar', $result->creation_method);
        $this->assertEquals('https://calendar.example/join', $result->joinWebUrl);
    }
}

class calendar_fallback_mock extends \mod_teamsmeetings\external\teams_api {
    public $direct_called = false;
    public $calendar_called = false;

    public function __construct() {
        // Do not call parent constructor. Set fallback flag via reflection.
        $ref = new ReflectionClass('\\mod_teamsmeetings\\external\\teams_api');
        $prop = $ref->getProperty('use_calendar_fallback');
        $prop->setAccessible(true);
        $prop->setValue($this, true);
    }

    protected function create_meeting_direct($data, $upn) {
        $this->direct_called = true;
        throw new Exception('fail');
    }

    protected function create_meeting_via_calendar($data, $upn) {
        $this->calendar_called = true;
        $result = new stdClass();
        $result->id = '1';
        $result->joinWebUrl = 'https://calendar.example/join';
        return $result;
    }
}
