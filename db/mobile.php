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
 * Mobile services definition for mod_teamsmeetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_teamsmeetings' => [
        'handlers' => [
            'teamsmeetings' => [
                'displaydata' => [
                    'title' => 'pluginname',
                    'icon' => $CFG->wwwroot . '/mod/teamsmeetings/pix/icon.png'
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_course_view',
                'offlinefunctions' => []
            ]
        ],
        'lang' => [
            ['pluginname', 'teamsmeetings']
        ]
    ]
];

$functions = [
    'mod_teamsmeetings_mobile_course_view' => [
        'classname'   => 'mod_teamsmeetings\\external\\mobile',
        'methodname'  => 'mobile_course_view',
        'description' => 'Return information to render a Teams Meeting in the mobile app.',
        'type'        => 'read',
        'capabilities'=> 'mod/teamsmeetings:view',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ]
];
