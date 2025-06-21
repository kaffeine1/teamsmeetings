<?php
function xmldb_teamsmeetings_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025061602) {
        $table = new xmldb_table('teamsmeetings');
        $field = new xmldb_field('organizer', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'join_url');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2025061602, 'teamsmeetings');
    }

    if ($oldversion < 2025061604) {
        $table = new xmldb_table('teamsmeetings');
        $field = new xmldb_field('creation_method', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'organizer');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2025061604, 'teamsmeetings');
    }

    return true;
}
