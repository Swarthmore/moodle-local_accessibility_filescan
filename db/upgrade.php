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
 * Local accessibility filescan plugin upgrade code.
 *
 * @package    local_accessibility_filescan
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die

function xmldb_local_accessibility_filescan_upgrade($oldversion) {

  global $DB;

  $dbmain = $DB->get_manager();

  if ($oldversion < 2024050800) {

      // Define field id to be added to local_a11y_filescan_pivot.
      $table = new xmldb_table('local_a11y_filescan_pivot');
      $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

      // Conditionally launch add field id.
      if (!$dbman->field_exists($table, $field)) {
          $dbman->add_field($table, $field);
      }

      // Accessibility_filescan savepoint reached.
      upgrade_plugin_savepoint(true, 2024050800, 'local', 'accessibility_filescan');
  }

  return true;

}
