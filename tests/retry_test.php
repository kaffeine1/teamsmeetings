<?php
// Tests the retry logic on HTTP 429 responses.

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/bootstrap.php');

class mod_teamsmeetings_retry_testcase extends advanced_testcase {
    public function test_retry_on_429() {
        if (!class_exists('curl', false)) {
            class_alias('dummy_curl', 'curl');
        }

        set_config('tenant_id', 't', 'mod_teamsmeetings');
        set_config('client_id', 'c', 'mod_teamsmeetings');
        set_config('client_secret', 's', 'mod_teamsmeetings');

        dummy_curl::$queue = [
            ['http_code' => 200, 'body' => json_encode(['access_token' => 'tok', 'expires_in' => 3600])],
            ['http_code' => 429, 'headers' => ['Retry-After' => 0], 'body' => '{}'],
            ['http_code' => 200, 'body' => json_encode(['id' => '1', 'joinWebUrl' => 'https://example.com/join'])]
        ];

        $api = new \mod_teamsmeetings\external\teams_api();

        $data = new stdClass();
        $data->name = 'Test';
        $data->start_time = time();

        $result = $api->create_meeting($data, 'user@example.com');
        $this->assertEquals('https://example.com/join', $result->joinWebUrl);
    }
}

class dummy_curl {
    public static $queue = [];
    private $info = [];
    private $headers = [];
    public function setHeader($h) {}
    public function post($u, $d) { return $this->next(); }
    public function get($u) { return $this->next(); }
    public function patch($u, $d) { return $this->next(); }
    public function delete($u) { return $this->next(); }
    private function next() {
        $res = array_shift(self::$queue);
        $this->info = ['http_code' => $res['http_code']];
        $this->headers = $res['headers'] ?? [];
        return $res['body'];
    }
    public function get_info() { return $this->info; }
    public function getResponseHeaders() { return $this->headers; }
}
