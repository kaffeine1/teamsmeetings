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
 * External functions used by the mobile app.
 *
 * @package    mod_teamsmeetings
 * @copyright  2025 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_teamsmeetings\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use context_module;

/**
 * Mobile external API class.
 */
class mobile extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function mobile_course_view_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    /**
     * Returns the main view for the mobile app.
     *
     * @param int $courseid Course id
     * @param int $cmid Course module id
     * @return array
     */
    public static function mobile_course_view(int $courseid, int $cmid): array {
        global $DB, $OUTPUT;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_id('teamsmeetings', $cmid, $courseid, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/teamsmeetings:view', $context);

        $meeting = $DB->get_record('teamsmeetings', ['id' => $cm->instance], '*', MUST_EXIST);

        $data = [
            'intro' => format_module_intro('teamsmeetings', $meeting, $cm->id),
            'starttime' => userdate($meeting->start_time),
            'endtime' => $meeting->end_time ? userdate($meeting->end_time) : '',
            'joinurl' => $meeting->join_url
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_teamsmeetings/mobile_view_page', $data)
                ]
            ]
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function mobile_course_view_returns() {
        return new external_single_structure([
            'templates' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_ALPHANUMEXT, 'Template id'),
                    'html' => new external_value(PARAM_RAW, 'HTML code')
                ])
            )
        ]);
    }
}
