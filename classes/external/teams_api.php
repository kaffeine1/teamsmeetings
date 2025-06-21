<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Microsoft Teams API integration
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_teamsmeetings\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Teams API class for managing meetings
 */
class teams_api {
    
    /** @var string Microsoft Graph API base URL */
    const GRAPH_API_URL = 'https://graph.microsoft.com/v1.0';
    
    /** @var string OAuth2 token endpoint */
    const TOKEN_ENDPOINT = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token';
    
    /** @var string Access token */
    private $access_token;
    
    /** @var string Tenant ID */
    private $tenant_id;
    
    /** @var string Client ID */
    private $client_id;
    
    /** @var string Client secret */
    private $client_secret;

    /** @var float Delay between consecutive API calls in seconds */
    private $rate_limit_delay = 1.0;

    /** @var float Timestamp of the last API call */
    private $last_api_call = 0.0;

    /** @var int Number of retries when receiving a 429 response */
    private $max_retries = 3;

    /** @var bool Debug mode flag */
    private $debug_mode = false;

    /** @var bool Fallback to calendar event creation */
    private $use_calendar_fallback = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->tenant_id = get_config('mod_teamsmeetings', 'tenant_id');
        $this->client_id = get_config('mod_teamsmeetings', 'client_id');
        $this->client_secret = get_config('mod_teamsmeetings', 'client_secret');

        $this->debug_mode = get_config('mod_teamsmeetings', 'debug_mode');
        $this->use_calendar_fallback = get_config('mod_teamsmeetings', 'use_calendar_fallback');
        
        $this->get_access_token();
    }
    
    /**
     * Get access token from Microsoft
     * * @return string
     */
    private function get_access_token() {
        global $DB;
        
        // Check if we have a valid cached token
        $cache = \cache::make('mod_teamsmeetings', 'oauth_token');
        $cached_token = $cache->get('access_token');
        
        if ($cached_token && $cached_token['expires'] > time()) {
            $this->access_token = $cached_token['token'];
            return $this->access_token;
        }
        
        // Request new token
        $token_url = str_replace('{tenant}', $this->tenant_id, self::TOKEN_ENDPOINT);
        
        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials'
        );
        
        $curl = new \curl();
        $response = $this->request_with_retry($curl, 'post', $token_url, $params);
        $data = json_decode($response);
        
        if (isset($data->access_token)) {
            $this->access_token = $data->access_token;
            
            // Cache token
            $cache->set('access_token', array(
                'token' => $data->access_token,
                'expires' => time() + $data->expires_in - 300 // 5 minutes buffer
            ));
            
            return $this->access_token;
        }
        
        throw new \moodle_exception('failedtogetaccesstoken', 'mod_teamsmeetings', '', null, $response);
    }

    /**
     * Public accessor to retrieve a valid access token for debugging purposes.
     *
     * @return string
     */
    public function get_access_token_debug() {
        return $this->get_access_token();
    }

    /**
     * Rate limit API calls to avoid hitting service limits
     */
    private function rate_limit_check() {
        $now = microtime(true);
        if ($this->last_api_call > 0) {
            $elapsed = $now - $this->last_api_call;
            if ($elapsed < $this->rate_limit_delay) {
                $sleep = ($this->rate_limit_delay - $elapsed) * 1000000;
                usleep((int)$sleep);
            }
        }
        $this->last_api_call = microtime(true);
    }

    /**
     * Execute a cURL request with retry on HTTP 429 responses.
     *
     * @param \curl $curl
     * @param string $method get|post|patch|delete
     * @param string $url
     * @param mixed $data
     * @return string response body
     * @throws \moodle_exception after exceeding retries
     */
    private function request_with_retry(\curl $curl, string $method, string $url, $data = null) {
        $attempt = 0;
        $delay = 1;

        do {
            $this->rate_limit_check();

            switch ($method) {
                case 'get':
                    $response = $curl->get($url);
                    break;
                case 'post':
                    $response = $curl->post($url, $data);
                    break;
                case 'patch':
                    $response = $curl->patch($url, $data);
                    break;
                case 'delete':
                    $response = $curl->delete($url);
                    break;
                default:
                    throw new \coding_exception('Invalid HTTP method: ' . $method);
            }

            $info = $curl->get_info();
            $code = $info['http_code'] ?? 0;
            if ($code != 429) {
                return $response;
            }

            $attempt++;

            // Check Retry-After header if available.
            $retryafter = 0;
            if (method_exists($curl, 'getResponseHeaders')) {
                $headers = array_change_key_case($curl->getResponseHeaders(), CASE_LOWER);
                if (isset($headers['retry-after'])) {
                    $retryafter = (int)$headers['retry-after'];
                }
            } elseif (isset($info['retry_after'])) {
                $retryafter = (int)$info['retry_after'];
            }

            $wait = $retryafter > 0 ? $retryafter : $delay;
            if ($wait > 0) {
                sleep($wait);
            }
            if ($retryafter <= 0) {
                $delay *= 2;
            }
        } while ($attempt <= $this->max_retries);

        throw new \moodle_exception('ratelimitexceeded', 'mod_teamsmeetings');
    }
    
    /**
     * Create a Teams meeting
     * * @param stdClass $meeting_data
     * @param string $organizerupn
     * @return stdClass|false
     */
    public function create_meeting($meeting_data, $organizerupn) {
        try {
            return $this->create_meeting_direct($meeting_data, $organizerupn);
        } catch (\Exception $e) {
            if ($this->debug_mode) {
                debugging('Direct meeting creation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
            if ($this->use_calendar_fallback) {
                if ($this->debug_mode) {
                    debugging('Falling back to calendar API', DEBUG_DEVELOPER);
                }
                return $this->create_meeting_via_calendar($meeting_data, $organizerupn);
            }
            throw $e;
        }
    }

    /**
     * Create a Teams meeting using onlineMeetings API
     *
     * @param stdClass $meeting_data
     * @param string $organizerupn
     * @return stdClass|false
     */
    private function create_meeting_direct($meeting_data, $organizerupn) {
        $organizerupn = trim($organizerupn);
        if (empty($organizerupn)) {
            throw new \coding_exception('Organizer UPN cannot be empty');
        }

        // Debug organizer used for the API request.
        if ($this->debug_mode) {
            debugging('Creating meeting for organizer UPN: ' . $organizerupn, DEBUG_DEVELOPER);
        }

        $url = self::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '/onlineMeetings';

        // Debug request URL.
        if ($this->debug_mode) {
            debugging('Teams API request URL: ' . $url, DEBUG_DEVELOPER);
        }
        
        // Convert Moodle times to ISO format
        $start_time = date('c', $meeting_data->start_time);
        $end_time = isset($meeting_data->end_time) && $meeting_data->end_time ? 
                    date('c', $meeting_data->end_time) : 
                    date('c', $meeting_data->start_time + 3600);
        
        $meeting = array(
            'subject' => $meeting_data->name,
            'startDateTime' => $start_time,
            'endDateTime' => $end_time,
            'allowedPresenters' => 'roleIsPresenter',
            'isEntryExitAnnounced' => true,
            'lobbyBypassSettings' => array(
                'scope' => $meeting_data->auto_admit_users ?? 'organization',
                'isDialInBypassEnabled' => true
            ),
            'allowMeetingChat' => 'enabled',
            'allowTeamworkReactions' => true,
            'allowAttendeeToEnableMic' => true,
            'allowAttendeeToEnableCamera' => true,
            'recordAutomatically' => !empty($meeting_data->record_meeting)
        );
        
        if (!empty($meeting_data->intro)) {
            $meeting['description'] = strip_tags($meeting_data->intro);
        }

        // Debug payload before adding recurrence.
        if ($this->debug_mode) {
            debugging('Teams API request payload: ' . json_encode($meeting), DEBUG_DEVELOPER);
        }
        
        // Add recurrence if needed
        if (!empty($meeting_data->recurring)) {
            $meeting['recurrence'] = $this->build_recurrence_pattern($meeting_data);
        }

        // Debug final payload including recurrence.
        if ($this->debug_mode) {
            debugging('Teams API final payload: ' . json_encode($meeting), DEBUG_DEVELOPER);
        }
        
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        );

        // Debug request headers.
        if ($this->debug_mode) {
            debugging('Teams API request headers: ' . json_encode($headers), DEBUG_DEVELOPER);
        }
        
        $curl = new \curl();
        $curl->setHeader($headers);
        $response = $this->request_with_retry($curl, 'post', $url, json_encode($meeting));

        // Debug raw response and HTTP status.
        $info = $curl->get_info();
        if ($this->debug_mode) {
            debugging('Teams API HTTP status: ' . ($info['http_code'] ?? 'unknown'), DEBUG_DEVELOPER);
            debugging('Teams API raw response: ' . $response, DEBUG_DEVELOPER);
        }
        
        $result = json_decode($response);

        // Debug decoded response.
        if ($this->debug_mode) {
            debugging('Teams API decoded response: ' . json_encode($result), DEBUG_DEVELOPER);
        }

        // The API response should contain the join URL in the joinWebUrl field.

        if (isset($result->id)) {
            return $result;
        }

        $message = isset($result->error->message) ? $result->error->message : $response;
        throw new \moodle_exception('meetingcreationerror', 'teamsmeetings', '', $message, $response);
    }

    /**
     * Create a Teams meeting by creating a calendar event.
     *
     * @param stdClass $meeting_data
     * @param string $organizerupn
     * @return stdClass
     */
    private function create_meeting_via_calendar($meeting_data, $organizerupn) {
        $organizerupn = trim($organizerupn);
        if (empty($organizerupn)) {
            throw new \coding_exception('Organizer UPN cannot be empty');
        }

        if ($this->debug_mode) {
            debugging('Creating meeting via calendar for ' . $organizerupn, DEBUG_DEVELOPER);
        }

        $url = self::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '/events';

        $event = array(
            'subject' => $meeting_data->name,
            'isOnlineMeeting' => true,
            'onlineMeetingProvider' => 'teamsForBusiness',
            'start' => array(
                'dateTime' => date('c', $meeting_data->start_time),
                'timeZone' => 'UTC'
            ),
            'end' => array(
                'dateTime' => date('c', $meeting_data->end_time ?? ($meeting_data->start_time + 3600)),
                'timeZone' => 'UTC'
            )
        );

        if (!empty($meeting_data->intro)) {
            $event['body'] = array('contentType' => 'HTML', 'content' => strip_tags($meeting_data->intro));
        }

        if (!empty($meeting_data->recurring)) {
            $event['recurrence'] = $this->build_calendar_recurrence_pattern($meeting_data);
        }

        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        );

        $curl = new \curl();
        $curl->setHeader($headers);
        $response = $this->request_with_retry($curl, 'post', $url, json_encode($event));

        if ($this->debug_mode) {
            $info = $curl->get_info();
            debugging('Calendar API HTTP status: ' . ($info['http_code'] ?? 'unknown'), DEBUG_DEVELOPER);
            debugging('Calendar API raw response: ' . $response, DEBUG_DEVELOPER);
        }

        $result = json_decode($response);

        if (isset($result->onlineMeeting)) {
            $meeting = $result->onlineMeeting;
            if (!isset($meeting->id) && isset($result->id)) {
                $meeting->id = $result->id;
            }
            if (isset($meeting->joinUrl) && !isset($meeting->joinWebUrl)) {
                $meeting->joinWebUrl = $meeting->joinUrl;
            }
            return $meeting;
        }

        $message = isset($result->error->message) ? $result->error->message : $response;
        throw new \moodle_exception('meetingcreationerror', 'teamsmeetings', '', $message, $response);
    }
    
    /**
     * Update a Teams meeting
     * * @param stdClass $meeting_data
     * @param string $organizerupn
     * @return bool
     */
    public function update_meeting($meeting_data, $organizerupn) {
        $organizerupn = trim($organizerupn);
        if (empty($organizerupn)) {
            throw new \coding_exception('Organizer UPN cannot be empty');
        }

        if (empty($meeting_data->meeting_id)) {
            return false;
        }

        $url = self::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '/onlineMeetings/' . $meeting_data->meeting_id;
        
        $update_data = array(
            'subject' => $meeting_data->name,
            'startDateTime' => date('c', $meeting_data->start_time),
            'endDateTime' => date('c', $meeting_data->end_time)
        );
        
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        );
        
        $curl = new \curl();
        $curl->setHeader($headers);
        $response = $this->request_with_retry($curl, 'patch', $url, json_encode($update_data));

        $result = json_decode($response);
        if ($response === false || isset($result->error)) {
            $message = isset($result->error->message) ? $result->error->message : $response;
            throw new \moodle_exception('meetingupdateerror', 'teamsmeetings', '', $message, $response);
        }

        return true;
    }
    
    /**
     * Delete a Teams meeting
     *
     * @param string $meeting_id Meeting identifier
     * @param string $organizerupn Organizer UPN
     * @return bool
     */
    public function delete_meeting($meeting_id, string $organizerupn) {
        $organizerupn = trim($organizerupn);
        if (empty($organizerupn)) {
            throw new \coding_exception('Organizer UPN cannot be empty');
        }
        
        $url = self::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '/onlineMeetings/' . $meeting_id;
        
        $headers = array(
            'Authorization: Bearer ' . $this->access_token
        );
        
        $curl = new \curl();
        $curl->setHeader($headers);
        $response = $this->request_with_retry($curl, 'delete', $url);

        $result = json_decode($response);
        if ($response === false || isset($result->error)) {
            $message = isset($result->error->message) ? $result->error->message : $response;
            throw new \moodle_exception('meetingdeleteerror', 'teamsmeetings', '', $message, $response);
        }

        return true;
    }
    
    /**
     * Get attendance report for a meeting
     * NOTA: Richiede OnlineMeetingArtifact.Read.All permission
     * e potrebbe richiedere l'uso dell'endpoint Beta
     * * @param string $meeting_id
     * @param string $organizerupn
     * @return array|false
     */
    public function get_attendance($meeting_id, $organizerupn) {
        $organizerupn = trim($organizerupn);
        if (empty($organizerupn)) {
            throw new \coding_exception('Organizer UPN cannot be empty');
        }

        // Usa endpoint Beta per attendance reports
        $url = 'https://graph.microsoft.com/beta/users/' . rawurlencode($organizerupn) . '/onlineMeetings/' . $meeting_id . '/attendanceReports';
        
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        );
        
        $curl = new \curl();
        $curl->setHeader($headers);
        $response = $this->request_with_retry($curl, 'get', $url);
        
        $result = json_decode($response);
        
        // Gestione errore specifico per permessi insufficienti
        if (isset($result->error)) {
            if ($result->error->code === 'Forbidden' || $result->error->code === 'Unauthorized') {
                $message = 'Permessi insufficienti per accedere ai report presenze. ' .
                        'Verificare che l\'app abbia il permesso OnlineMeetingArtifact.Read.All';
                throw new \moodle_exception('syncerror', 'teamsmeetings', '', null, $message);
            }
            throw new \moodle_exception('syncerror', 'teamsmeetings', '', null, $result->error->message);
        }
        
        if (isset($result->value) && !empty($result->value)) {
            $latest_report = end($result->value);
            return $this->get_attendance_report_details($meeting_id, $latest_report->id, $organizerupn);
        }
        
        return false;
    }
    
    /**
     * Get detailed attendance report
     * * @param string $meeting_id
     * @param string $report_id
     * @param string $organizerupn
     * @return array|false
     */
    private function get_attendance_report_details($meeting_id, $report_id, $organizerupn) {
        $organizerupn = trim($organizerupn);
        if (empty($organizerupn)) {
            throw new \coding_exception('Organizer UPN cannot be empty');
        }

        $url = self::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '/onlineMeetings/' . $meeting_id . 
               '/attendanceReports/' . $report_id . '/attendanceRecords';
        
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        );
        
        $curl = new \curl();
        $curl->setHeader($headers);
        $response = $this->request_with_retry($curl, 'get', $url);
        
        $result = json_decode($response);

        if (isset($result->value)) {
            return $this->process_attendance_records($result->value, $meeting_id);
        }

        if (isset($result->error)) {
            throw new \moodle_exception('syncerror', 'teamsmeetings', '', null, $result->error->message);
        }

        throw new \moodle_exception('syncerror', 'teamsmeetings', '', null, 'Unexpected response when retrieving attendance details: ' . $response);
    }
    
    /**
     * Process attendance records from Teams API
     * * @param array $records
     * @param int $meetingid
     * @return array
     */
    private function process_attendance_records($records, $meetingid) {
        global $DB;
        
        $processed = array();
        $unmatched = array();
        
        foreach ($records as $record) {
            // Prova a matchare l'utente
            $user = $this->match_teams_user($record);
            
            if ($user) {
                // Utente trovato - processa normalmente
                $attendance = new \stdClass();
                $attendance->userid = $user->id;
                $attendance->email = $record->emailAddress;
                $attendance->display_name = $record->identity->displayName;
                $attendance->role = $record->role;
                $attendance->total_duration = $this->parse_duration($record->totalAttendanceInSeconds);
                
                // Process attendance intervals
                if (isset($record->attendanceIntervals)) {
                    $attendance->sessions = array();
                    foreach ($record->attendanceIntervals as $interval) {
                        $session = new \stdClass();
                        $session->join_time = strtotime($interval->joinDateTime);
                        $session->leave_time = strtotime($interval->leaveDateTime);
                        $session->duration = $interval->durationInSeconds;
                        $attendance->sessions[] = $session;
                    }
                }
                
                $processed[] = $attendance;
            } else {
                // Utente non trovato - salva nei non mappati
                $unmatched_user = new \stdClass();
                $unmatched_user->meetingid = $meetingid;
                $unmatched_user->teams_email = $record->emailAddress ?? '';
                $unmatched_user->teams_displayname = $record->identity->displayName ?? '';
                $unmatched_user->teams_id = $record->identity->id ?? '';
                $unmatched_user->duration = $record->totalAttendanceInSeconds ?? 0;
                $unmatched_user->timecreated = time();
                
                // Prendi prima e ultima sessione
                if (isset($record->attendanceIntervals) && !empty($record->attendanceIntervals)) {
                    $first = reset($record->attendanceIntervals);
                    $last = end($record->attendanceIntervals);
                    $unmatched_user->join_time = strtotime($first->joinDateTime);
                    $unmatched_user->leave_time = strtotime($last->leaveDateTime);
                }
                
                // Verifica se esiste già
                $existing = $DB->get_record('teamsmeetings_unmatched_users', array(
                    'meetingid' => $meetingid,
                    'teams_email' => $unmatched_user->teams_email
                ));
                
                if (!$existing) {
                    $DB->insert_record('teamsmeetings_unmatched_users', $unmatched_user);
                } else {
                    // Aggiorna con ultimi dati
                    $unmatched_user->id = $existing->id;
                    $DB->update_record('teamsmeetings_unmatched_users', $unmatched_user);
                }
                
                $unmatched[] = $unmatched_user;
            }
        }
        
        // Se ci sono utenti non mappati, notifica
        if (!empty($unmatched) && $this->debug_mode) {
            debugging('Found ' . count($unmatched) . ' unmatched Teams users for meeting ' . $meetingid, DEBUG_DEVELOPER);
        }
        
        return $processed;
    }
    
    /**
     * Match Teams user with Moodle user
     * * @param stdClass $teams_user
     * @return stdClass|false
     */
    private function match_teams_user($teams_user) {
        global $DB;
        
        // Try to match by email
        if (!empty($teams_user->emailAddress)) {
            return $DB->get_record('user', array('email' => $teams_user->emailAddress));
        }
        
        // Try to match by username (if using AAD sync)
        if (!empty($teams_user->identity->id)) {
            return $DB->get_record('user', array('username' => $teams_user->identity->id));
        }
        
        return false;
    }
    
    /**
     * Build recurrence pattern for meeting
     * * @param stdClass $meeting_data
     * @return array
     */
    private function build_recurrence_pattern($meeting_data) {
        $pattern = array(
            'pattern' => array(
                'type' => $meeting_data->recurrence_pattern ?? 'weekly',
                'interval' => 1
            ),
            'range' => array(
                'type' => 'endDate',
                'startDate' => date('Y-m-d', $meeting_data->start_time),
                'endDate' => date('Y-m-d', $meeting_data->recurrence_end ?? strtotime('+6 months'))
            )
        );
        
        // Add day of week for weekly pattern
        if ($pattern['pattern']['type'] == 'weekly') {
            $pattern['pattern']['daysOfWeek'] = array(strtolower(date('l', $meeting_data->start_time)));
        }
        
        return $pattern;
    }

    /**
     * Build recurrence pattern for calendar events.
     *
     * @param stdClass $meeting_data
     * @return array
     */
    private function build_calendar_recurrence_pattern($meeting_data) {
        $pattern = array(
            'pattern' => array(
                'type' => $meeting_data->recurrence_pattern ?? 'weekly',
                'interval' => 1
            ),
            'range' => array(
                'type' => 'endDate',
                'startDate' => date('Y-m-d', $meeting_data->start_time),
                'endDate' => date('Y-m-d', $meeting_data->recurrence_end ?? strtotime('+6 months'))
            )
        );

        if ($pattern['pattern']['type'] == 'weekly') {
            $pattern['pattern']['daysOfWeek'] = array(ucfirst(strtolower(date('l', $meeting_data->start_time))));
        }

        return $pattern;
    }
    
    /**
     * Parse duration from seconds to human readable
     * * @param int $seconds
     * @return string
     */
    private function parse_duration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    
    /**
     * Get meeting join URL
     * * @param string $meeting_id
     * @param string $organizerupn
     * @return string|false
     */
    public function get_join_url($meeting_id, $organizerupn) {
        $organizerupn = trim($organizerupn);
        if (empty($organizerupn)) {
            throw new \coding_exception('Organizer UPN cannot be empty');
        }

        $url = self::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '/onlineMeetings/' . $meeting_id;
        
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        );
        
        $curl = new \curl();
        $curl->setHeader($headers);
        $response = $this->request_with_retry($curl, 'get', $url);

        $result = json_decode($response);

        if (isset($result->joinWebUrl)) {
            return $result->joinWebUrl;
        }

        if ($this->use_calendar_fallback) {
            $url = self::GRAPH_API_URL . '/users/' . rawurlencode($organizerupn) . '/events/' . $meeting_id . '?$select=onlineMeeting';
            $curl = new \curl();
            $curl->setHeader($headers);
            $response = $this->request_with_retry($curl, 'get', $url);
            $result = json_decode($response);
            if (isset($result->onlineMeeting->joinWebUrl)) {
                return $result->onlineMeeting->joinWebUrl;
            }
            if (isset($result->onlineMeeting->joinUrl)) {
                return $result->onlineMeeting->joinUrl;
            }
        }

        return false;
    }
}