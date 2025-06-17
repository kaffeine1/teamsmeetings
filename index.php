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
 * List all Teams Meeting activities in a course.
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);
$context = context_course::instance($course->id);

$PAGE->set_url('/mod/teamsmeetings/index.php', ['id' => $course->id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

// Output page header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'teamsmeetings'));

$meetings = get_all_instances_in_course('teamsmeetings', $course);
if (!$meetings) {
    echo $OUTPUT->notification(get_string('nomeetings', 'teamsmeetings'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$format = course_get_format($course);
$usesections = $format->uses_sections();
$modinfo = get_fast_modinfo($course);
$sections = $modinfo->get_section_info_all();

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->attributes['role'] = 'table';
$table->caption = get_string('modulenameplural', 'teamsmeetings');
$table->head = [];
$table->align = [];
if ($usesections) {
    $table->head[] = get_string('sectionname');
    $table->align[] = 'center';
}
$table->head = array_merge($table->head, [
    get_string('name'),
    get_string('starttime', 'teamsmeetings'),
    get_string('endtime', 'teamsmeetings')
]);
$table->align = array_merge($table->align, ['left', 'left', 'left']);

foreach ($meetings as $meeting) {
    $cm = $modinfo->instances['teamsmeetings'][$meeting->id];
    $row = [];
    if ($usesections) {
        $section = $sections[$cm->sectionnum] ?? null;
        $row[] = $section ? get_section_name($course, $section) : '';
    }
    $link = html_writer::link(new moodle_url('/mod/teamsmeetings/view.php', ['id' => $cm->id]),
                              format_string($meeting->name));
    $row[] = $link;
    $row[] = $meeting->start_time ? userdate($meeting->start_time) : '-';
    $row[] = $meeting->end_time ? userdate($meeting->end_time) : '-';
    $table->data[] = $row;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
