<?php
// Tests that creating a meeting stores join url from API.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/bootstrap.php');

class mod_teamsmeetings_join_url_testcase extends advanced_testcase {
    public function test_join_url_stored() {
        global $DB;

        // Provide a fake teams_api before it is autoloaded.
        if (!class_exists('mod_teamsmeetings\\external\\teams_api', false)) {
            class_alias('dummy_api', 'mod_teamsmeetings\\external\\teams_api');
        }

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_teamsmeetings');
        $meeting = $generator->create_instance(['course' => $course->id, 'name' => 'Test']);

        $record = $DB->get_record('teamsmeetings', ['id' => $meeting], '*', MUST_EXIST);
        $this->assertEquals('https://example.com/meeting', $record->join_url);
    }
}

class dummy_api {
    public function create_meeting($data, $organizer) {
        $result = new stdClass();
        $result->id = '1';
        $result->joinWebUrl = 'https://example.com/meeting';
        return $result;
    }
}
