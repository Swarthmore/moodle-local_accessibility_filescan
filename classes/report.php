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
 * PDF helper functions local_a11y_check
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * Report generator functions.
 */
class report {
    public static function generate_report() {
        global $DB;
        $sql = "SELECT f.scanid, f.contenthash as contenthash,"
            . "f.pathnamehash as pathnamehash, f.hastext, f.hastitle, f.haslanguage,"
            . "f.istagged, f.pagecount, f.hasbookmarks, c.status, c.statustext,"
            . "c.lastchecked, files.filename "
            . "FROM {local_a11y_check_type_pdf} f "
            . "INNER JOIN {local_a11y_check} c ON c.id = f.scanid "
            . "INNER JOIN {files} files ON files.contenthash=f.contenthash";
        $limit = 1000;
        $files = $DB->get_records_sql($sql, null, 0, $limit);
        return $files;
    }
}
