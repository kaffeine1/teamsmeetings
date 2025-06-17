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
 * Attendance report for Teams meetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/teamsmeetings/lib.php');
require_once($CFG->libdir . '/tablelib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$download = optional_param('download', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('teamsmeetings', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$teamsmeetings = $DB->get_record('teamsmeetings', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/teamsmeetings:viewattendance', $context);

$PAGE->set_url('/mod/teamsmeetings/attendance.php', array('id' => $cm->id));
$PAGE->set_title(get_string('attendancereport', 'teamsmeetings'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Log event
$event = \mod_teamsmeetings\event\attendance_viewed::create(array(
    'objectid' => $teamsmeetings->id,
    'context' => $context,
));
$event->trigger();

// If showing details for specific user
if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('attendancedetailsfor', 'teamsmeetings', fullname($user)));
    
    // Back button
    $backurl = new moodle_url('/mod/teamsmeetings/attendance.php', array('id' => $cm->id));
    echo html_writer::div(
        html_writer::link($backurl, get_string('back'), array('class' => 'btn btn-secondary')),
        'mb-3'
    );
    
    // Get user sessions
    $sessions = teamsmeetings_get_user_sessions($teamsmeetings->id, $userid);
    
    if ($sessions) {
        $table = new html_table();
        $table->head = array(
            get_string('session', 'teamsmeetings'),
            get_string('jointime', 'teamsmeetings'),
            get_string('leavetime', 'teamsmeetings'),
            get_string('duration', 'teamsmeetings'),
            get_string('ipaddress', 'teamsmeetings')
        );
        $table->attributes['class'] = 'generaltable';
        
        $sessionnum = 1;
        $totalduration = 0;
        
        foreach ($sessions as $session) {
            $jointime = userdate($session->join_time, get_string('strftimedatetimeshort'));
            $leavetime = $session->leave_time ? userdate($session->leave_time, get_string('strftimedatetimeshort')) : 
                        get_string('stillconnected', 'teamsmeetings');
            $duration = $session->duration ? format_time($session->duration) : '-';
            
            $table->data[] = array(
                $sessionnum++,
                $jointime,
                $leavetime,
                $duration,
                $session->ip_address ?: '-'
            );
            
            $totalduration += $session->duration;
        }
        
        // Add total row
        $table->data[] = array(
            html_writer::tag('strong', get_string('total', 'teamsmeetings')),
            '',
            '',
            html_writer::tag('strong', format_time($totalduration)),
            ''
        );
        
        echo html_writer::table($table);
    } else {
        echo html_writer::div(get_string('nosessions', 'teamsmeetings'), 'alert alert-info');
    }
    
    echo $OUTPUT->footer();
    exit;
}

// Main attendance report
$table = new flexible_table('mod-teamsmeetings-attendance');

$tablecolumns = array('fullname', 'email', 'firstjoin', 'lastleave', 'duration', 'sessions', 'attendance', 'actions');
$tableheaders = array(
    get_string('fullname'),
    get_string('email'),
    get_string('firstjoin', 'teamsmeetings'),
    get_string('lastleave', 'teamsmeetings'),
    get_string('totalduration', 'teamsmeetings'),
    get_string('sessions', 'teamsmeetings'),
    get_string('attendancepercent', 'teamsmeetings'),
    get_string('actions')
);

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('attendancereport', 'teamsmeetings') . ': ' . format_string($teamsmeetings->name));
    
    // Meeting info
    echo html_writer::start_tag('div', array('class' => 'meetinginfo mb-3'));
    echo html_writer::tag('p', get_string('starttime', 'teamsmeetings') . ': ' . 
                         userdate($teamsmeetings->start_time));
    if ($teamsmeetings->end_time) {
        echo html_writer::tag('p', get_string('endtime', 'teamsmeetings') . ': ' . 
                             userdate($teamsmeetings->end_time));
        
        $meetingduration = $teamsmeetings->end_time - $teamsmeetings->start_time;
        echo html_writer::tag('p', get_string('meetingduration', 'teamsmeetings') . ': ' . 
                             format_time($meetingduration));
    }
    echo html_writer::end_tag('div');
    
    // Sync button (to get latest data from Teams)
    if (has_capability('mod/teamsmeetings:managemeeting', $context)) {
        $syncurl = new moodle_url('/mod/teamsmeetings/sync.php', array(
            'id' => $cm->id,
            'sesskey' => sesskey()
        ));
        echo html_writer::div(
            html_writer::link($syncurl, get_string('syncattendance', 'teamsmeetings'), 
                             array('class' => 'btn btn-primary')),
            'mb-3'
        );
    }
} else {
    // Remove actions column for download
    $key = array_search('actions', $tablecolumns);
    unset($tablecolumns[$key]);
    unset($tableheaders[$key]);
}

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($PAGE->url);

$table->sortable(true, 'fullname');
$table->collapsible(true);
$table->initialbars(true);

$table->column_class('fullname', 'fullname');
$table->column_class('email', 'email');
$table->column_class('duration', 'duration text-center');
$table->column_class('sessions', 'sessions text-center');
$table->column_class('attendance', 'attendance text-center');

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'attendancereport');
$table->set_attribute('class', 'generaltable generalbox');

$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloadable(true);
$table->is_downloading($download, 'teams_attendance_' . $teamsmeetings->id);

$table->setup();

// Get enrolled users
// Prima ottieni tutti gli utenti iscritti
$enrolledusers = get_enrolled_users($context, 'mod/teamsmeetings:view', 0, 'u.*', 'lastname, firstname');

// Poi ottieni i dati delle presenze (una riga per utente)
$sql = "SELECT ta.userid, ta.join_time, ta.leave_time, ta.duration,
               (SELECT COUNT(DISTINCT ts.sessionid) 
                FROM {teamsmeetings_sessions} ts 
                WHERE ts.userid = ta.userid AND ts.meetingid = ta.meetingid) as session_count
        FROM {teamsmeetings_attendance} ta
        WHERE ta.meetingid = :meetingid";

$attendancedata = $DB->get_records_sql($sql, ['meetingid' => $teamsmeetings->id]);

// Calcola durata meeting per percentuale presenza
$meetingduration = 0;
if ($teamsmeetings->end_time > 0) {
    $meetingduration = $teamsmeetings->end_time - $teamsmeetings->start_time;
} else {
    // Se il meeting è ancora in corso, usa il tempo attuale
    $meetingduration = time() - $teamsmeetings->start_time;
}

// Costruisci la tabella iterando sugli utenti iscritti
foreach ($enrolledusers as $user) {
    $row = array();
    
    // Link al profilo utente
    $userurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id));
    $row[] = html_writer::link($userurl, fullname($user));
    $row[] = $user->email;
    
    // Verifica se l'utente ha dati di presenza
    if (isset($attendancedata[$user->id])) {
        $attendance = $attendancedata[$user->id];
        
        $row[] = userdate($attendance->join_time, get_string('strftimedatetimeshort'));
        $row[] = $attendance->leave_time ? 
                 userdate($attendance->leave_time, get_string('strftimedatetimeshort')) : 
                 get_string('stillconnected', 'teamsmeetings');
        $row[] = format_time($attendance->duration);
        $row[] = $attendance->session_count;
        
        // Calcola percentuale presenza
        if ($meetingduration > 0) {
            $percentage = min(100, round(($attendance->duration / $meetingduration) * 100));
            $badgeclass = 'badge ';
            if ($percentage >= 80) {
                $badgeclass .= 'badge-success';
            } else if ($percentage >= 50) {
                $badgeclass .= 'badge-warning';
            } else {
                $badgeclass .= 'badge-danger';
            }
            $row[] = html_writer::tag('span', $percentage . '%', array('class' => $badgeclass));
        } else {
            $row[] = '-';
        }
    } else {
        // Utente non ha partecipato
        $row[] = '-';
        $row[] = '-';
        $row[] = '0';
        $row[] = '0';
        $row[] = html_writer::tag('span', '0%', array('class' => 'badge badge-danger'));
    }
    
    if (!$table->is_downloading()) {
        // Colonna azioni
        $detailsurl = new moodle_url('/mod/teamsmeetings/attendance.php', array(
            'id' => $cm->id,
            'userid' => $user->id
        ));
        $row[] = html_writer::link($detailsurl, get_string('viewdetails', 'teamsmeetings'), 
                                  array('class' => 'btn btn-sm btn-secondary'));
    }
    
    $table->add_data($row);
}

$table->finish_output();

if (!$table->is_downloading()) {
    // Export options
    echo html_writer::start_tag('div', array('class' => 'exportoptions mt-3'));
    echo html_writer::tag('h4', get_string('exportoptions', 'teamsmeetings'));
    
    $exporturl = new moodle_url($PAGE->url);
    echo html_writer::link(new moodle_url($exporturl, array('download' => 'csv')), 
                          get_string('downloadcsv', 'teamsmeetings'), 
                          array('class' => 'btn btn-secondary mr-2'));
    echo html_writer::link(new moodle_url($exporturl, array('download' => 'excel')), 
                          get_string('downloadexcel', 'teamsmeetings'), 
                          array('class' => 'btn btn-secondary mr-2'));
    echo html_writer::link(new moodle_url($exporturl, array('download' => 'pdf')), 
                          get_string('downloadpdf', 'teamsmeetings'), 
                          array('class' => 'btn btn-secondary'));
    
    echo html_writer::end_tag('div');
    
    echo $OUTPUT->footer();
}