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
 * English language strings for mod_teamsmeetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Teams Meeting';
$string['modulenameplural'] = 'Teams Meetings';
$string['modulename_help'] = 'The Teams Meeting module allows teachers to create and manage Microsoft Teams meetings directly from Moodle courses with automatic attendance tracking.';
$string['pluginname'] = 'Teams Meeting';
$string['pluginadministration'] = 'Teams Meeting administration';

// Capabilities
$string['teamsmeetings:addinstance'] = 'Add a new Teams Meeting';
$string['teamsmeetings:view'] = 'View Teams Meetings';
$string['teamsmeetings:viewattendance'] = 'View attendance reports';
$string['teamsmeetings:exportattendance'] = 'Export attendance reports';
$string['teamsmeetings:managemeeting'] = 'Manage Teams Meeting';
$string['teamsmeetings:syncattendance'] = 'Sync attendance from Teams';
$string['teamsmeetings:deleteattendance'] = 'Delete attendance records';

// Form fields
$string['meetingname'] = 'Meeting name';
$string['meetingname_help'] = 'Name shown in the course and in Microsoft Teams';
$string['meetingsettings'] = 'Meeting settings';
$string['starttime'] = 'Start time';
$string['starttime_help'] = 'Date and time when the meeting starts';
$string['endtime'] = 'End time';
$string['endtime_help'] = 'Date and time when the meeting ends (optional)';
$string['recurring'] = 'Recurring meeting';
$string['recurring_help'] = 'Enable if the meeting repeats regularly';
$string['recurrencepattern'] = 'Recurrence pattern';
$string['recurrenceend'] = 'Recurrence end';
$string['daily'] = 'Daily';
$string['weekly'] = 'Weekly';
$string['monthly'] = 'Monthly';

// Teams options
$string['teamsoptions'] = 'Microsoft Teams options';
$string['allowanonymous'] = 'Allow anonymous access';
$string['allowanonymous_help'] = 'Allows users to join without authentication';
$string['autoadmitusers'] = 'Automatically admit users';
$string['everyoneincompany'] = 'Everyone in the organization';
$string['everyone'] = 'Everyone';
$string['everyoneincompanyexcludingguests'] = 'Everyone in the organization except guests';
$string['organizeronly'] = 'Organizer only';
$string['recordmeeting'] = 'Record meeting';
$string['recordmeeting_help'] = 'Automatically record the meeting when it starts';
$string['updatemeeting'] = 'Update Teams meeting';
$string['updatemeeting_help'] = 'If enabled, changes here will also update the associated meeting in Microsoft Teams.';

// Attendance tracking
$string['attendancetracking'] = 'Attendance tracking';
$string['trackattendance'] = 'Track attendance';
$string['trackattendance_help'] = 'Enable automatic attendance tracking';
$string['minduration'] = 'Minimum duration (minutes)';
$string['minduration_help'] = 'Minimum presence time to be marked present';

// Notifications
$string['notifications'] = 'Notifications';
$string['sendreminder'] = 'Send reminder';
$string['remindertime'] = 'Reminder time';
$string['minutes15'] = '15 minutes before';
$string['minutes30'] = '30 minutes before';
$string['hour1'] = '1 hour before';
$string['day1'] = '1 day before';

// Validation errors
$string['endtimebeforestarttime'] = 'End time must be after start time';
$string['invalidminduration'] = 'Minimum duration must be at least 1 minute';

// View page
$string['meetingdetails'] = 'Meeting details';
$string['status'] = 'Status';
$string['scheduled'] = 'Scheduled';
$string['inprogress'] = 'In progress';
$string['ended'] = 'Ended';
$string['joinmeeting'] = 'Join meeting';
$string['meetingnotstartedyet'] = 'The meeting has not started yet';
$string['meetinghasended'] = 'The meeting has ended';
$string['nojoinurl'] = 'Join URL not available';
$string['meetinglink'] = 'Meeting link';
$string['openinteams'] = 'Open in Microsoft Teams';
$string['cannotjoinyet'] = 'You cannot join the meeting yet';
$string['cannotjoinended'] = 'The meeting has already ended';

// Attendance report
$string['attendancereport'] = 'Attendance report';
$string['viewattendancereport'] = 'View attendance report';
$string['participant'] = 'Participant';
$string['firstjoin'] = 'First join';
$string['lastleave'] = 'Last leave';
$string['totalduration'] = 'Total duration';
$string['sessions'] = 'Sessions';
$string['noattendancedata'] = 'No attendance data available';
$string['attendancedetailsfor'] = 'Attendance details for {$a}';
$string['session'] = 'Session';
$string['jointime'] = 'Join time';
$string['leavetime'] = 'Leave time';
$string['duration'] = 'Duration';
$string['ipaddress'] = 'IP address';
$string['stillconnected'] = 'Still connected';
$string['total'] = 'Total';
$string['nosessions'] = 'No sessions found';
$string['attendancepercent'] = 'Attendance %';
$string['viewdetails'] = 'View details';
$string['meetingduration'] = 'Meeting duration';
$string['syncattendance'] = 'Sync attendance';
$string['lastsync'] = 'Last synchronization';

// Export
$string['exportoptions'] = 'Export options';
$string['downloadcsv'] = 'Download CSV';
$string['downloadexcel'] = 'Download Excel';
$string['downloadpdf'] = 'Download PDF';

// Plugin settings
$string['settings'] = 'Teams Meeting settings';
$string['tenant_id'] = 'Tenant ID';
$string['tenant_id_desc'] = 'Microsoft Azure Tenant ID of your organization';
$string['client_id'] = 'Client ID';
$string['client_id_desc'] = 'Application (client) ID from the Azure registered app';
$string['client_secret'] = 'Client secret';
$string['client_secret_desc'] = 'Client secret from the Azure registered app';

// Events
$string['eventmeetingcreated'] = 'Teams meeting created';
$string['eventmeetingjoined'] = 'Teams meeting joined';
$string['eventattendanceviewed'] = 'Attendance report viewed';
$string['eventattendancelogged'] = 'Attendance logged';
$string['eventattendancesynced'] = 'Attendance synchronized';
$string['eventcoursemoduleviewed'] = 'Teams Meeting module viewed';

// Backup
$string['attendancerecords'] = 'Attendance records';

// Errors
$string['failedtogetaccesstoken'] = 'Could not obtain the access token from Microsoft';
$string['missingparameter'] = 'Missing parameter';
$string['nomeetingid'] = 'Teams meeting ID not found';
$string['failedtosyncattendance'] = 'Unable to synchronize attendance data';
$string['syncpartialcomplete'] = 'Partial sync: {$a->synced} users synced, {$a->errors} errors';
$string['synccomplete'] = 'Sync completed: {$a} users synced';
$string['syncerror'] = 'Error while synchronizing attendance';
$string['meetingcreationerror'] = 'Failed to create meeting: {$a}';
$string['meetingupdateerror'] = 'Failed to update meeting: {$a}';
$string['meetingdeleteerror'] = 'Failed to delete meeting: {$a}';
$string['syncerrorwithmsg'] = 'Error while synchronizing attendance: {$a}';

// Advanced settings
$string['configintro'] = 'Configure the integration with Microsoft Teams to allow meeting creation and attendance tracking.';
$string['azuresettings'] = 'Azure Active Directory settings';
$string['azuresettings_desc'] = 'Configure the credentials of the app registered in Azure AD to access Microsoft Graph APIs.';
$string['defaultmeetingsettings'] = 'Default meeting settings';
$string['defaultmeetingsettings_desc'] = 'Default values for new Teams meetings.';
$string['defaultautoadmit'] = 'Default automatic admission';
$string['defaultautoadmit_desc'] = 'Who can bypass the lobby by default';
$string['defaultrecording'] = 'Default recording';
$string['defaultrecording_desc'] = 'Enable automatic recording by default';
$string['defaultattendance'] = 'Default attendance tracking';
$string['defaultattendance_desc'] = 'Enable attendance tracking by default';
$string['defaultminduration'] = 'Default minimum duration';
$string['defaultminduration_desc'] = 'Minimum minutes of presence required to be considered present (default)';
$string['cronsettings'] = 'Synchronization settings';
$string['cronsettings_desc'] = 'Configure how often to synchronize attendance data from Teams.';
$string['syncfrequency'] = 'Sync frequency';
$string['syncfrequency_desc'] = 'How often to sync attendance data from Microsoft Teams';
$string['minutes5'] = '5 minutes';
$string['minutes10'] = '10 minutes';
$string['advancedsettings'] = 'Advanced settings';
$string['advancedsettings_desc'] = 'Advanced options for debugging and optimisation.';
$string['debugmode'] = 'Debug mode';
$string['debugmode_desc'] = 'Enable detailed logs for troubleshooting';
$string['cacheduration'] = 'Cache duration (seconds)';
$string['cacheduration_desc'] = 'How long to keep access tokens in cache';
$string['use_calendar_fallback'] = 'Use calendar fallback';
$string['use_calendar_fallback_desc'] = 'If direct meeting creation fails, attempt to create the meeting via the organizer\'s calendar.';

// Organizer UPN field
$string['upnfield'] = 'Organizer UPN field';
$string['upnfield_desc'] = 'Select which user field should be used as the organizer UPN when creating Teams meetings.';
$string['invalidupn'] = 'The organizer UPN is not a valid email address';

// Beta endpoint warning
$string['betaendpointwarning'] = 'Attendance relies on Microsoft Graph Beta APIs, which may change or stop working.';

// Scheduled tasks
$string['syncattendancetask'] = 'Teams Meeting attendance sync';
$string['syncsinglemeetingtask'] = 'Sync single Teams Meeting attendance';

// General messages
$string['nomeetings'] = 'No Teams Meetings in this course';
$string['meetingcreatedviacalendar'] = 'Meeting created successfully using Calendar integration (fallback method)';
$string['meetingcreatedviadirect'] = 'Meeting created successfully using direct Teams API';
$string['calendarfallbackinfo'] = 'This meeting was created using Calendar integration due to license limitations';
$string['directapiinfo'] = 'This meeting was created using direct Teams API';
$string['creationmethod'] = 'Creation method';
$string['createdviadirect'] = 'Created via direct API';
$string['createdviacalendar'] = 'Created via calendar fallback';

// Unmatched users
$string['unmatchedusers'] = 'Unrecognised Teams users';
$string['backtoattendance'] = 'Back to attendance report';
$string['nounmatchedusers'] = 'All Teams users were recognised correctly.';
$string['unmatchedusers_help'] = 'These users joined the Teams meeting but were not automatically recognised. You can manually map them to course users.';
$string['teamsemail'] = 'Teams email';
$string['teamsdisplayname'] = 'Teams display name';
$string['maptouser'] = 'Map to Moodle user';
$string['selectuser'] = 'Select user...';
$string['map'] = 'Map';
$string['usermapped'] = 'User successfully mapped and attendance updated.';
$string['testconnection'] = 'Test connection';
$string['connectionsuccessful'] = 'Connection successful';
$string['connectionfailed'] = 'Connection failed: {$a}';

// Debug page strings.
$string['debugpage'] = 'Debug Teams Meeting';
$string['debugpage_desc'] = 'Run a test meeting creation and display detailed output.';
$string['configcheck'] = 'Configuration check';
$string['configmissing'] = 'Configuration incomplete! Please review the plugin settings.';
$string['upncheck'] = 'Organizer UPN verification';
$string['apitest'] = 'API test';
$string['licensecheck'] = 'License check';
$string['teamsmeetingcapability'] = 'Teams meeting capability';

// Privacy API
$string['privacy:metadata:teamsmeetings_attendance'] = 'Stores attendance summary for each meeting.';
$string['privacy:metadata:teamsmeetings_attendance:meetingid'] = 'Meeting instance ID.';
$string['privacy:metadata:teamsmeetings_attendance:userid'] = 'ID of the user.';
$string['privacy:metadata:teamsmeetings_attendance:join_time'] = 'Timestamp when the user first joined.';
$string['privacy:metadata:teamsmeetings_attendance:leave_time'] = 'Timestamp when the user left.';
$string['privacy:metadata:teamsmeetings_attendance:duration'] = 'Total time spent in the meeting.';
$string['privacy:metadata:teamsmeetings_attendance:sessionid'] = 'Identifier of the related session.';
$string['privacy:metadata:teamsmeetings_sessions'] = 'Stores detailed session data for each user.';
$string['privacy:metadata:teamsmeetings_sessions:meetingid'] = 'Meeting instance ID.';
$string['privacy:metadata:teamsmeetings_sessions:userid'] = 'ID of the user.';
$string['privacy:metadata:teamsmeetings_sessions:sessionid'] = 'Session identifier.';
$string['privacy:metadata:teamsmeetings_sessions:join_time'] = 'Timestamp when the session started.';
$string['privacy:metadata:teamsmeetings_sessions:leave_time'] = 'Timestamp when the session ended.';
$string['privacy:metadata:teamsmeetings_sessions:duration'] = 'Duration of the session.';
$string['privacy:metadata:teamsmeetings_sessions:ip_address'] = 'IP address used in the session.';
$string['privacy:metadata:teamsmeetings_unmatched_users'] = 'Stores details for Teams users that could not be matched.';
$string['privacy:metadata:teamsmeetings_unmatched_users:meetingid'] = 'Meeting instance ID.';
$string['privacy:metadata:teamsmeetings_unmatched_users:teams_email'] = 'Email address from Teams.';
$string['privacy:metadata:teamsmeetings_unmatched_users:teams_displayname'] = 'Display name in Teams.';
$string['privacy:metadata:teamsmeetings_unmatched_users:teams_id'] = 'Teams unique identifier.';
$string['privacy:metadata:teamsmeetings_unmatched_users:join_time'] = 'Join time recorded from Teams.';
$string['privacy:metadata:teamsmeetings_unmatched_users:leave_time'] = 'Leave time recorded from Teams.';
$string['privacy:metadata:teamsmeetings_unmatched_users:duration'] = 'Duration recorded from Teams.';
$string['privacy:metadata:teamsmeetings_unmatched_users:mapped_userid'] = 'Manually mapped Moodle user.';
$string['privacy:metadata:teamsmeetings_unmatched_users:timecreated'] = 'Time when the record was created.';