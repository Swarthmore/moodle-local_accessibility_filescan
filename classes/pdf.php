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
 * @copyright 2023 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

use Exception;
use Throwable;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * PDF helper functions
 */
class pdf {

    /**
     * Remove all rows that don't have a record in mdl_files (draft context is ignored).
     * @throws \dml_exception
     */
    public static function cleanup_orphaned_records(): void {
        global $DB;

        $sql = 'select lacp.scanid as "scanid", lacp.fileid as "fileid", lacp.courseid as "courseid" '.
            'from {local_a11y_check_queue} lacq ' .
            'inner join {local_a11y_check_pivot} lacp on lacq.id = lacp.scanid '.
            'left outer join {files} f on f.id = lacp.fileid '.
            'where f.id is null';

        $records = $DB->get_records_sql($sql);

        mtrace('Found ' . count($records) . ' orphaned records');

        foreach ($records as $record) {
            // Remove record from the PDF results table.
            $DB->delete_records('local_a11y_check_type_pdf', ['scanid' => $record->scanid]);
            // Remove record from the queue table.
            $DB->delete_records('local_a11y_check_queue', ['id' => $record->scanid]);
            // Remove record from the pivot table.
            $DB->delete_records('local_a11y_check_pivot',
                ['scanid' => $record->scanid, 'fileid' => $record->fileid, 'courseid' => $record->courseid]);
        }

    }

    /**
     * Find all PDF files that do not have a record in the queue.
     * @throws \dml_exception
     */
    public static function get_unqueued_files(): array {
        global $DB;

        $sql = 'select f.id as "fileid", f.filesize as "filesize", f.filename as "filename", '.
            'c.id as "courseid", c.shortname as "courseshortname", c.fullname as "coursefullname" '.
            'from {files} f '.
            'inner join {context} ctx on ctx.id = f.contextid  '.
            'inner join {course_modules} cm on cm.id = ctx.instanceid '.
            'inner join {course} c on c.id = cm.course '.
            'left outer join {local_a11y_check_pivot} lacp on lacp.fileid = f.id and lacp.courseid = c.id '.
            'left outer join {local_a11y_check_queue} lacq on lacq.id = lacp.scanid '.
            'where ctx.contextlevel = 70 '.
            'and lacq.id is null '.
            "and f.mimetype = 'application/pdf'";

        $files = [];
        $recordset = $DB->get_recordset_sql($sql);

        if ($recordset->valid()) {
            foreach ($recordset as $record) {
                $files[] = $record;
            }
        }

        $recordset->close();
        return $files;
    }

    /**
     * Return the ids of all PDF files in the queue, but have not been scanned for accessibility.
     */
    public static function get_unscanned_files(): array {
        global $DB;

        $sql = 'select lacp.fileid as "fileid", lacq.id as "scanid" '.
            'from {local_a11y_check_queue} lacq '.
            'inner join {local_a11y_check_pivot} lacp on lacp.scanid = lacq.id '.
            'inner join {files} f on f.id = lacp.fileid '.
            'where lacq.status = 0  '.
            "and f.mimetype = 'application/pdf' ".
            'limit 25';

        $files = [];
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $files[] = $record;
        }
        $recordset->close();
        return $files;
    }

    /**
     * Create a queue record a single PDF.
     * @param mixed $file The file object. (Must have filesize, courseid, and fileid).
     * @return void
     * @throws \dml_exception
     */
    public static function put_file_in_queue($file): void {
        global $DB;

        // Check if the file exceeds the max filesize set in the config.
        $maxfilesize = (int) get_config('local_a11y_check', 'max_file_size_mb');
        $canprocess = (bool) $file->filesize <= $maxfilesize;

        $now = time();

        // Insert the record into the queue table.
        $scanid = $DB->insert_record('local_a11y_check_queue', [
            'checktype' => LOCAL_A11Y_CHECK_TYPE_PDF,
            'faildelay' => $now + 120,
            'lastchecked' => $now,
            'status' => $canprocess ? LOCAL_A11Y_CHECK_STATUS_UNCHECKED : LOCAL_A11Y_CHECK_STATUS_IGNORE,
            'statustext' => $canprocess ? null : 'File exceeds max filesize'
        ]);

        $DB->execute('INSERT INTO {local_a11y_check_pivot} (courseid, scanid, fileid) VALUES (?,?,?)', [
            $file->courseid,
            $scanid,
            $file->fileid
        ]);

        // Insert the record into the pivot table.
//        $DB->insert_record('local_a11y_check_pivot', [
//            'courseid' => $file->courseid,
//            'fileid' => $file->fileid,
//            'scanid' => $scanid
//        ], false);

    }

    /**
     * Scan queued files (at random) and returns its accessibility results.
     * @return void
     * @throws \dml_exception
     */
    public static function scan_queued_files(): void {

        global $DB;

        $files = self::get_unscanned_files();

        if (count($files) == 0) {
            mtrace('No files found');
            return;
        } else {
            mtrace('Found '. count($files) . ' files to scan');
        }

        foreach ($files as $file) {
            try {

                mtrace("Scanning $file->fileid");

                $tmpfile = self::create_tmp_file($file->fileid);
                $results = \local_a11y_check\pdf_scanner::scan($tmpfile);
                $record = [
                    'scanid' => $file->scanid,
                    'hastext' => $results->hastext,
                    'hastitle' => $results->hastitle,
                    'haslanguage' => $results->haslanguage,
                    'hasbookmarks' => $results->hasbookmarks,
                    'istagged' => $results->istagged,
                    'pagecount' => $results->pagecount
                ];
                // Create the results record that will be inserted into the PDF results table.
                // Insert the results into the PDF results table.
                $DB->insert_record('local_a11y_check_type_pdf', $record);
                // Update the scan status in the queue table.
                $DB->update_record('local_a11y_check_queue', (object) [
                    'id' => $file->scanid,
                    'status' => 1,
                    'lastchecked' => time()
                ]);
                // Make sure to delete tmpfile!
                unlink($tmpfile);
            } catch (Exception | Throwable $e) {
                // If there's an error, get the message and update the queue table.
                $DB->update_record('local_a11y_check_queue', (object) [
                    'id' => $file->scanid,
                    'status' => 4,
                    'statustext' => $e->getMessage(),
                    'lastchecked' => time()
                ]);
            }

        }

    }

    /**
     * Create a tmp file in the Moodle temp file storage area and return the path.
     * @param $fileid int
     * @return string
     */
    private static function create_tmp_file(int $fileid): string {
        global $CFG;
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($fileid);
        $content = $file->get_content();
        // Copy the file to a temp directory so that it can be scanned.
        $tmpfile = $CFG->dataroot . '/temp/filestorage/' . $file->get_pathnamehash() . '.pdf';
        file_put_contents($tmpfile, $content);
        return $tmpfile;
    }

}
