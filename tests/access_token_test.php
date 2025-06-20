<?php
// Tests for get_access_token_debug.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/bootstrap.php');

class mod_teamsmeetings_access_token_testcase extends advanced_testcase {
    public function test_get_access_token_debug_returns_constructor_token() {
        if (!class_exists('curl', false)) {
            class_alias('dummy_curl_token', 'curl');
        }

        set_config('tenant_id', 't', 'mod_teamsmeetings');
        set_config('client_id', 'c', 'mod_teamsmeetings');
        set_config('client_secret', 's', 'mod_teamsmeetings');

        dummy_curl_token::$queue = [
            ['http_code' => 200, 'body' => json_encode(['access_token' => 'tok123', 'expires_in' => 3600])]
        ];

        $api = new \mod_teamsmeetings\external\teams_api();
        $token = $api->get_access_token_debug();

        $this->assertEquals('tok123', $token);
    }
}

class dummy_curl_token {
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
        $this->info = ['http_code' => $res['http_code'] ?? 0];
        $this->headers = $res['headers'] ?? [];
        return $res['body'] ?? '';
    }
    public function get_info() { return $this->info; }
    public function getResponseHeaders() { return $this->headers; }
}
