# API Documentation

This document describes the public PHP API provided by the plugin for integrating with Microsoft Teams.

## teams_api class

Located at `classes/external/teams_api.php`, this class wraps calls to Microsoft Graph to manage Teams meetings.

### Public methods

- `__construct()` – Reads plugin settings and obtains an access token from Azure AD.
- `create_meeting(stdClass $meeting_data, string $organizerupn): stdClass` – Creates a meeting and returns the response from Microsoft Graph. The method first attempts a direct call and, if that fails and the `use_calendar_fallback` setting is enabled, retries using the calendar API.
- `update_meeting(stdClass $meeting_data, string $organizerupn): bool` – Updates a meeting with new times or subject.
- `delete_meeting(string $meeting_id, string $organizerupn, ?bool $viaCalendar = null): bool` – Deletes a meeting. When `$viaCalendar` is true or the ID resembles a calendar event, the calendar events endpoint is used.
- `get_attendance(string $meeting_id, string $organizerupn): array|false` – Retrieves attendance data for a finished meeting.
- `get_join_url(string $meeting_id, string $organizerupn): string|false` – Returns the join URL for a meeting.

### Example usage

```php
use mod_teamsmeetings\external\teams_api;

$api = new teams_api();

// Create a meeting
$meeting = new stdClass();
$meeting->name = 'Planning meeting';
$meeting->start_time = time() + 3600; // 1 hour from now
$meeting->end_time = $meeting->start_time + 3600; // duration 1 hour
$result = $api->create_meeting($meeting, 'organizer@example.com');

// Fetch attendance after the meeting
$attendance = $api->get_attendance($result->id, 'organizer@example.com');
```

This API can be called from custom Moodle code or external scripts that bootstrap the Moodle environment. Ensure the plugin configuration (client ID, secret and tenant ID) is set before instantiating `teams_api`.


