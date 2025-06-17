<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');

class restore_teamsmeetings_activity_task extends restore_activity_task {

    protected function define_my_settings() {
        // No specific settings.
    }

    protected function define_my_steps() {
        $this->add_step(new restore_teamsmeetings_activity_structure_step('teamsmeetings_structure', 'teamsmeetings.xml'));
    }

    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('teamsmeetings', ['intro'], 'teamsmeetings');
        return $contents;
    }

    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('TEAMSMEETINGSVIEWBYID', '/mod/teamsmeetings/view.php?id=$1', 'course_module');
        return $rules;
    }
}
