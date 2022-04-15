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

    /**
     * Returns an array of all scanned files.
     * @param number $limit
     * @return array
     */
    public static function generate_report($limit = 1000) {
        global $DB;

        $sql = "SELECT actp.id, actp.scanid, actp.contenthash as contenthash,"
            . "actp.pathnamehash as pathnamehash, actp.hastext, actp.hastitle, actp.haslanguage,"
            . "actp.istagged, actp.pagecount, actp.hasbookmarks, ac.status, ac.statustext,"
            . "ac.lastchecked, f.filename, f.contextid, f.component, f.filepath, f.filearea, f.itemid "
            . "FROM {local_a11y_check_type_pdf} actp "
            . "INNER JOIN {local_a11y_check} ac ON ac.id = actp.scanid "
            . "INNER JOIN {files} f ON f.pathnamehash = actp.pathnamehash";
        $recordset = $DB->get_recordset_sql($sql, null, 0, $limit);
        $files = array();

        foreach ($recordset as $record) {
            $file_url = \moodle_url::make_pluginfile_url(
                $record->contextid,
                $record->component,
                $record->filearea,
                $record->itemid,
                $record->filepath,
                $record->filename,
                false
            );
            $record->fileurl = $file_url->out(false);
            array_push($files, $record);
        }
        $recordset->close();
        return $files;
    }

    /**
     * Provides a download URL to a generated CSV report.
     * @return string
     */
    public static function generate_csv() {

        global $CFG;

        $report = self::generate_report();
        $fields = array(
            array('Filename', 'Has Text', 'Has Title', 'Has Language', 'Is Tagged', 'Page Count')
        );
        foreach ($report as $row) {
            $csvrow = array(
                $row->filename,
                $row->hastext,
                $row->hastitle,
                $row->haslanguage,
                $row->istagged,
                $row->pagecount
            );
            array_push($fields, $csvrow);
        }
        $now = (string) time();
        $filename = $now . '_a11y-check-pdfs.csv';
        $filepath = $CFG->dataroot . '/temp/filestorage/' . $filename;
        $fh = fopen($filepath, 'w');
        foreach ($fields as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);
        return $filepath;
    }

}
