<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Data generator for Teams Meetings module.
 */
class mod_teamsmeetings_generator extends testing_module_generator {
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;
        if (!isset($record->name)) {
            $record->name = 'Teams meeting';
        }
        if (!isset($record->start_time)) {
            $record->start_time = time();
        }
        if (!isset($record->end_time)) {
            $record->end_time = $record->start_time + 3600;
        }
        if (!isset($record->recurring)) {
            $record->recurring = 0;
        }
        return parent::create_instance($record, (array)$options);
    }
}
