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
 * View Teams meeting
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/teamsmeetings/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID
$t = optional_param('t', 0, PARAM_INT); // Teams meeting instance ID
$action = optional_param('action', '', PARAM_ALPHA);

if ($id) {
    $cm = get_coursemodule_from_id('teamsmeetings', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $teamsmeetings = $DB->get_record('teamsmeetings', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($t) {
    $teamsmeetings = $DB->get_record('teamsmeetings', array('id' => $t), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $teamsmeetings->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('teamsmeetings', $teamsmeetings->id, $course->id, false, MUST_EXIST);
} else {
    print_error('missingparameter');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Handle join action
if ($action == 'join' && confirm_sesskey()) {
    require_capability('mod/teamsmeetings:view', $context);
    
    // Trigger event
    $event = \mod_teamsmeetings\event\meeting_joined::create(array(
        'objectid' => $teamsmeetings->id,
        'context' => $context,
    ));
    $event->trigger();
    
    // Redirect to Teams meeting
    if ($teamsmeetings->join_url) {
        redirect($teamsmeetings->join_url);
    } else {
        print_error('nojoinurl', 'teamsmeetings');
    }
}

// Log view
$event = \mod_teamsmeetings\event\course_module_viewed::create(array(
    'objectid' => $cm->instance,
    'context' => $context,
));
$event->add_record_snapshot('teamsmeetings', $teamsmeetings);
$event->trigger();

// Mark as viewed for completion
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Page setup
$PAGE->set_url('/mod/teamsmeetings/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($teamsmeetings->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here
echo $OUTPUT->header();

// Display heading
echo $OUTPUT->heading(format_string($teamsmeetings->name), 2);

// Display introduction
if ($teamsmeetings->intro) {
    echo $OUTPUT->box(format_module_intro('teamsmeetings', $teamsmeetings, $cm->id), 
                      'generalbox mod_introbox', 'teamsintro');
}

// Meeting information box
$meetinginfo = new stdClass();
$meetinginfo->starttime = userdate($teamsmeetings->start_time);
if ($teamsmeetings->end_time) {
    $meetinginfo->endtime = userdate($teamsmeetings->end_time);
}

echo $OUTPUT->box_start('generalbox meetinginfo');
echo html_writer::tag('h3', get_string('meetingdetails', 'teamsmeetings'));

$table = new html_table();
$table->caption = get_string('meetingdetails', 'teamsmeetings');
$table->attributes['class'] = 'generaltable meetingdetails';
$table->attributes['role'] = 'table';

// Start time
$table->data[] = array(
    get_string('starttime', 'teamsmeetings'),
    $meetinginfo->starttime
);

// End time
if (isset($meetinginfo->endtime)) {
    $table->data[] = array(
        get_string('endtime', 'teamsmeetings'),
        $meetinginfo->endtime
    );
}

// Meeting status
$now = time();
if ($now < $teamsmeetings->start_time) {
    $status = html_writer::tag('span', get_string('scheduled', 'teamsmeetings'), 
                              array('class' => 'badge badge-info'));
} else if ($teamsmeetings->end_time && $now > $teamsmeetings->end_time) {
    $status = html_writer::tag('span', get_string('ended', 'teamsmeetings'), 
                              array('class' => 'badge badge-secondary'));
} else {
    $status = html_writer::tag('span', get_string('inprogress', 'teamsmeetings'), 
                              array('class' => 'badge badge-success'));
}

$table->data[] = array(
    get_string('status', 'teamsmeetings'),
    $status
);

// Recurring info
if ($teamsmeetings->recurring) {
    $table->data[] = array(
        get_string('recurring', 'teamsmeetings'),
        get_string('yes')
    );
}

echo html_writer::table($table);

// Join button
if ($teamsmeetings->join_url) {
    $joinurl = new moodle_url('/mod/teamsmeetings/view.php', array(
        'id' => $cm->id,
        'action' => 'join',
        'sesskey' => sesskey()
    ));
    
    $buttontext = get_string('joinmeeting', 'teamsmeetings');
    $buttonclass = 'btn btn-primary btn-lg';
    
    // Disable button if meeting hasn't started or has ended
    if ($now < $teamsmeetings->start_time - 900) { // Allow joining 15 minutes early
        $buttonclass .= ' disabled';
        $buttontext = get_string('meetingnotstartedyet', 'teamsmeetings');
    } else if ($teamsmeetings->end_time && $now > $teamsmeetings->end_time + 3600) { // Allow 1 hour after end
        $buttonclass .= ' disabled';
        $buttontext = get_string('meetinghasended', 'teamsmeetings');
    }
    
    echo html_writer::div(
        html_writer::link($joinurl, $buttontext, array('class' => $buttonclass)),
        'joinbutton text-center mt-3 mb-3'
    );
}

echo $OUTPUT->box_end();

// Show attendance report for teachers
if (has_capability('mod/teamsmeetings:viewattendance', $context)) {
    echo html_writer::tag('h3', get_string('attendancereport', 'teamsmeetings'), array('class' => 'mt-4'));
    
    $attendanceurl = new moodle_url('/mod/teamsmeetings/attendance.php', array('id' => $cm->id));
    echo html_writer::div(
        html_writer::link($attendanceurl, get_string('viewattendancereport', 'teamsmeetings'), 
                         array('class' => 'btn btn-secondary')),
        'text-center mb-3'
    );
    
    // Quick attendance summary
    $attendees = teamsmeetings_get_attendance_report($teamsmeetings->id);
    
    if ($attendees) {
        $table = new html_table();
        $table->caption = get_string('attendancereport', 'teamsmeetings');
        $table->attributes['role'] = 'table';
        $table->head = array(
            get_string('participant', 'teamsmeetings'),
            get_string('firstjoin', 'teamsmeetings'),
            get_string('lastleave', 'teamsmeetings'),
            get_string('totalduration', 'teamsmeetings'),
            get_string('sessions', 'teamsmeetings')
        );
        $table->attributes['class'] = 'generaltable attendancesummary';
        
        foreach ($attendees as $attendee) {
            $userlink = html_writer::link(
                new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id)),
                fullname($attendee)
            );
            
            $firstjoin = $attendee->join_time ? userdate($attendee->join_time, get_string('strftimedatetimeshort')) : '-';
            $lastleave = $attendee->leave_time ? userdate($attendee->leave_time, get_string('strftimedatetimeshort')) : '-';
            $duration = $attendee->duration ? format_time($attendee->duration) : '-';
            
            $table->data[] = array(
                $userlink,
                $firstjoin,
                $lastleave,
                $duration,
                $attendee->session_count
            );
        }
        
        echo html_writer::table($table);
    } else {
        echo html_writer::div(get_string('noattendancedata', 'teamsmeetings'), 'alert alert-info');
    }
}

// Show completion info
// Use $PAGE->cm here to ensure we have a cm_info object rather than a raw
// course_module record. This avoids type errors when retrieving completion
// details.
$completiondetails = \core_completion\cm_completion_details::get_instance($PAGE->cm, $USER->id);
$activitydates = \core\activity_dates::get_dates_for_module($PAGE->cm, $USER->id);
echo $OUTPUT->activity_information($PAGE->cm, $completiondetails, $activitydates);

echo $OUTPUT->footer();