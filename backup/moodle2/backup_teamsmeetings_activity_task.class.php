<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');

class backup_teamsmeetings_activity_task extends backup_activity_task {

    protected function define_my_settings() {
        // No specific settings for this activity.
    }

    protected function define_my_steps() {
        $this->add_step(new backup_teamsmeetings_activity_structure_step('teamsmeetings_structure', 'teamsmeetings.xml'));
    }

    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');

        $search = "/{$base}\/mod\/teamsmeetings\/view.php\?id=(\d+)/";
        $content = preg_replace($search, '$@TEAMSMEETINGSVIEWBYID*$1@$', $content);

        return $content;
    }
}
