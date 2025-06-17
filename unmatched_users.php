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
 * Display and map unmatched Teams users to Moodle users.
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/teamsmeetings/lib.php');

$cmid = required_param('id', PARAM_INT); // Course module id.
$mapid = optional_param('mapid', 0, PARAM_INT); // unmatched record id to map.
$userid = optional_param('userid', 0, PARAM_INT); // moodle user id.
require_sesskey();

$cm = get_coursemodule_from_id('teamsmeetings', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$teamsmeeting = $DB->get_record('teamsmeetings', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/teamsmeetings:syncattendance', $context);

$PAGE->set_url('/mod/teamsmeetings/unmatched_users.php', ['id' => $cmid]);
$PAGE->set_title(format_string($teamsmeeting->name));
$PAGE->set_heading(format_string($course->fullname));

$returnurl = new moodle_url('/mod/teamsmeetings/unmatched_users.php', ['id' => $cmid]);
$attendanceurl = new moodle_url('/mod/teamsmeetings/attendance.php', ['id' => $cmid]);

if ($mapid && $userid) {
    $record = $DB->get_record('teamsmeetings_unmatched_users', [
        'id' => $mapid,
        'meetingid' => $teamsmeeting->id,
        'mapped_userid' => 0
    ], '*', MUST_EXIST);

    $record->mapped_userid = $userid;
    $DB->update_record('teamsmeetings_unmatched_users', $record);

    // Create a single session and update attendance summary.
    $session = new stdClass();
    $session->meetingid = $teamsmeeting->id;
    $session->userid = $userid;
    $session->sessionid = uniqid('teams_');
    $session->join_time = $record->join_time;
    $session->leave_time = $record->leave_time;
    $session->duration = $record->duration;
    $DB->insert_record('teamsmeetings_sessions', $session);
    teamsmeetings_update_attendance_summary($teamsmeeting->id, $userid);

    redirect($returnurl, get_string('usermapped', 'teamsmeetings'), 0,
        core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('unmatchedusers', 'teamsmeetings'));

echo $OUTPUT->box(get_string('unmatchedusers_help', 'teamsmeetings'), 'generalbox mb-3');

$unmatched = $DB->get_records('teamsmeetings_unmatched_users', [
    'meetingid' => $teamsmeeting->id,
    'mapped_userid' => 0
]);

if (!$unmatched) {
    echo $OUTPUT->notification(get_string('nounmatchedusers', 'teamsmeetings'), 'info');
    echo $OUTPUT->continue_button($attendanceurl);
    echo $OUTPUT->footer();
    exit;
}

$enrolled = get_enrolled_users($context);
$options = ['' => get_string('selectuser', 'teamsmeetings')];
foreach ($enrolled as $user) {
    $options[$user->id] = fullname($user) . ' (' . $user->email . ')';
}

$table = new html_table();
$table->attributes['role'] = 'table';
$table->caption = get_string('unmatchedusers', 'teamsmeetings');
$table->head = [
    get_string('teamsemail', 'teamsmeetings'),
    get_string('teamsdisplayname', 'teamsmeetings'),
    get_string('maptouser', 'teamsmeetings')
];

foreach ($unmatched as $u) {
    $select = html_writer::select($options, 'userid', '', ['' => get_string('selectuser', 'teamsmeetings')]);

    $form = html_writer::start_tag('form', ['method' => 'post', 'action' => $returnurl]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mapid', 'value' => $u->id]);
    $form .= $select;
    $form .= html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-primary ml-2',
        'value' => get_string('map', 'teamsmeetings'),
        'name' => 'submit'
    ]);
    $form .= html_writer::end_tag('form');

    $table->data[] = [$u->teams_email, $u->teams_displayname, $form];
}

echo html_writer::table($table);

echo $OUTPUT->continue_button($attendanceurl, 'btn btn-secondary mt-3');

echo $OUTPUT->footer();
