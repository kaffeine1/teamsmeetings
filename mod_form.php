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
 * Form for creating and editing Teams meeting instances
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_teamsmeetings_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('meetingname', 'teamsmeetings'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'meetingname', 'teamsmeetings');

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        // Meeting settings
        $mform->addElement('header', 'meetingsettings', get_string('meetingsettings', 'teamsmeetings'));

        // Start time
        $mform->addElement('date_time_selector', 'start_time', get_string('starttime', 'teamsmeetings'));
        $mform->addHelpButton('start_time', 'starttime', 'teamsmeetings');

        // End time
        $mform->addElement('date_time_selector', 'end_time', get_string('endtime', 'teamsmeetings'), array('optional' => true));
        $mform->addHelpButton('end_time', 'endtime', 'teamsmeetings');

        // Recurring meeting
        $mform->addElement('selectyesno', 'recurring', get_string('recurring', 'teamsmeetings'));
        $mform->addHelpButton('recurring', 'recurring', 'teamsmeetings');
        $mform->setDefault('recurring', 0);

        // Recurrence pattern (shown only if recurring is yes)
        $recurrenceoptions = array(
            'daily' => get_string('daily', 'teamsmeetings'),
            'weekly' => get_string('weekly', 'teamsmeetings'),
            'monthly' => get_string('monthly', 'teamsmeetings')
        );
        $mform->addElement('select', 'recurrence_pattern', get_string('recurrencepattern', 'teamsmeetings'), $recurrenceoptions);
        $mform->hideIf('recurrence_pattern', 'recurring', 'eq', 0);

        // Recurrence end date
        $mform->addElement('date_selector', 'recurrence_end', get_string('recurrenceend', 'teamsmeetings'), array('optional' => true));
        $mform->hideIf('recurrence_end', 'recurring', 'eq', 0);

        // Teams meeting options
        $mform->addElement('header', 'teamsoptions', get_string('teamsoptions', 'teamsmeetings'));

        // Allow anonymous join
        $mform->addElement('selectyesno', 'allow_anonymous', get_string('allowanonymous', 'teamsmeetings'));
        $mform->addHelpButton('allow_anonymous', 'allowanonymous', 'teamsmeetings');
        $mform->setDefault('allow_anonymous', 0);

        // Auto admit users
        $autoadmitoptions = array(
            'EveryoneInCompany' => get_string('everyoneincompany', 'teamsmeetings'),
            'Everyone' => get_string('everyone', 'teamsmeetings'),
            'EveryoneInCompanyExcludingGuests' => get_string('everyoneincompanyexcludingguests', 'teamsmeetings'),
            'OrganizerOnly' => get_string('organizeronly', 'teamsmeetings')
        );
        $mform->addElement('select', 'auto_admit_users', get_string('autoadmitusers', 'teamsmeetings'), $autoadmitoptions);
        $mform->setDefault('auto_admit_users', 'EveryoneInCompany');

        // Record meeting
        $mform->addElement('selectyesno', 'record_meeting', get_string('recordmeeting', 'teamsmeetings'));
        $mform->addHelpButton('record_meeting', 'recordmeeting', 'teamsmeetings');
        $mform->setDefault('record_meeting', 0);

        // Update Teams meeting when editing existing instance.
        if ($this->_instance) {
            $mform->addElement('selectyesno', 'update_teams_meeting', get_string('updatemeeting', 'teamsmeetings'));
            $mform->addHelpButton('update_teams_meeting', 'updatemeeting', 'teamsmeetings');
            $mform->setDefault('update_teams_meeting', 0);
        }

        // Attendance tracking
        $mform->addElement('header', 'attendancetracking', get_string('attendancetracking', 'teamsmeetings'));

        // Enable attendance tracking
        $mform->addElement('selectyesno', 'track_attendance', get_string('trackattendance', 'teamsmeetings'));
        $mform->addHelpButton('track_attendance', 'trackattendance', 'teamsmeetings');
        $mform->setDefault('track_attendance', 1);

        // Minimum duration for attendance (in minutes)
        $mform->addElement('text', 'min_duration', get_string('minduration', 'teamsmeetings'));
        $mform->setType('min_duration', PARAM_INT);
        $mform->setDefault('min_duration', 10);
        $mform->addHelpButton('min_duration', 'minduration', 'teamsmeetings');
        $mform->hideIf('min_duration', 'track_attendance', 'eq', 0);

        // Notifications
        $mform->addElement('header', 'notifications', get_string('notifications', 'teamsmeetings'));

        // Send reminder
        $mform->addElement('selectyesno', 'send_reminder', get_string('sendreminder', 'teamsmeetings'));
        $mform->setDefault('send_reminder', 1);

        // Reminder time (minutes before meeting)
        $reminderoptions = array(
            '15' => get_string('minutes15', 'teamsmeetings'),
            '30' => get_string('minutes30', 'teamsmeetings'),
            '60' => get_string('hour1', 'teamsmeetings'),
            '1440' => get_string('day1', 'teamsmeetings')
        );
        $mform->addElement('select', 'reminder_time', get_string('remindertime', 'teamsmeetings'), $reminderoptions);
        $mform->setDefault('reminder_time', '30');
        $mform->hideIf('reminder_time', 'send_reminder', 'eq', 0);

        // Standard coursemodule elements
        $this->standard_coursemodule_elements();

        // Add standard buttons
        $this->add_action_buttons();
    }

    /**
     * Form validation
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate end time is after start time
        if (!empty($data['end_time']) && $data['end_time'] <= $data['start_time']) {
            $errors['end_time'] = get_string('endtimebeforestarttime', 'teamsmeetings');
        }

        // Validate minimum duration
        if ($data['track_attendance'] && (!isset($data['min_duration']) || $data['min_duration'] < 1)) {
            $errors['min_duration'] = get_string('invalidminduration', 'teamsmeetings');
        }

        return $errors;
    }

    /**
     * Preprocessing form data
     */
    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);
        
        // Set default start time to next hour if creating new
        if (empty($this->_instance)) {
            $nexthour = ceil(time() / 3600) * 3600;
            $default_values['start_time'] = $nexthour;
            $default_values['end_time'] = $nexthour + 3600; // 1 hour later
        }
    }
}