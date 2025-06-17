<?php
function xmldb_mod_teamsmeetings_upgrade($oldversion) {
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

    return true;
}
