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
 * local_a11y_check upgrade code.
 *
 * @package   local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool A status indicating success or failure
 */
function xmldb_local_a11y_check_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020021800) {

        $table = new xmldb_table('local_a11y_check');
        $field = new xmldb_field('statustext', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'status');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020021800, 'local', 'a11y_check');
    }

    if ($oldversion < 2020021914) {

        $table = new xmldb_table('local_a11y_check_courses');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2020021914, 'local', 'a11y_check');
    }

    if ($oldversion < 2021061502) {

        $table = new xmldb_table('local_a11y_check_type_pdf');
        $hasoutlinefield = new xmldb_field('hasoutline', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $hasbookmarksfield = new xmldb_field('hasbookmarks', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $pagecountfield = new xmldb_field('pagecount', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
        $istaggedfield = new xmldb_field('istagged', XMLDB_TYPE_INTEGER, '1', null, null, null, null);

        if (!$dbman->field_exists($table, $istaggedfield)) {
            $dbman->add_field($table, $istaggedfield);
        }

        if (!$dbman->field_exists($table, $pagecountfield)) {
            $dbman->add_field($table, $pagecountfield);
        }

        if ($dbman->field_exists($table, $hasoutlinefield)) {
            $dbman->rename_field($table, $hasoutlinefield, $hasbookmarksfield);
        }

        upgrade_plugin_savepoint(true, 2021061502, 'local', 'a11y_check');
    }

    return true;
}