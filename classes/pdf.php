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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * PDF helper functions
 */
class pdf {

    /**
     * Find all PDF files that do not have a record in the a11y_check queue.
     * @throws \dml_exception
     */
    public static function get_unqueued_files(): array {
        global $DB;

        $sql = <<<'SQL'
            select f.id as "file_id", f.filesize as "file_filesize", f.filename as "file_filename", c.id as "course_id", 
                   c.shortname as "course_shortname", c.fullname as "course_fullname" 
            from m_files f 
            inner join m_context ctx on ctx.id = f.contextid 
            inner join m_course_modules cm on cm.id = ctx.instanceid 
            inner join m_course c on c.id = cm.course 
            left outer join m_local_a11y_check_pivot lacp on lacp.fileid = f.id and lacp.courseid = c.id
            left outer join m_local_a11y_check_queue lacq on lacq.id = lacp.scanid
            where f.mimetype = 'application/pdf'
            and ctx.contextlevel = 70
            and lacq.id is null
        SQL;

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
     * Create the queue record a single PDF.
     * @param mixed $file The file object.
     *                    Must have file_filesize, course_id, and file_id
     * @return bool|int Returns the created record id on success.
     * @throws \dml_exception
     */
    public static function put_file_in_queue(mixed $file): bool|int {
        global $DB;

        $canprocess = self::is_under_max_filesize($file->file_filesize);

        $scanid = $DB->insert_record('local_a11y_check_queue', [
            'checktype' => LOCAL_A11Y_CHECK_TYPE_PDF,
            'faildelay' => 120,
            'lastchecked' => 0,
            'status' => $canprocess ? LOCAL_A11Y_CHECK_STATUS_UNCHECKED : LOCAL_A11Y_CHECK_STATUS_IGNORE,
            'statustext' => $canprocess ? null : 'File exceeds max filesize'
        ]);

        $DB->insert_record('local_a11y_check_pivot', [
            'courseid' => $file->course_id,
            'fileid' => $file->file_id,
            'scanid' => $scanid
        ], false);

        return $scanid;

    }


    /**
     * Remove all rows that don't have a record in mdl_files (draft context is ignored).
     * @throws \dml_exception
     */
    public static function cleanup_orphaned_records(): void {
        global $DB;

        $sql = <<< 'SQL'
            select lacp.scanid as "scanid", lacp.fileid as "fileid", lacp.courseid as "courseid" 
            from {local_a11y_check_queue} lacq
            inner join {local_a11y_check_pivot} lacp on lacq.id = lacp.scanid
            left outer join {files} f on f.id = lacp.fileid
            where f.id is null;
        SQL;

        $records = $DB->get_records_sql($sql);

        mtrace('Found ' . count($records) . ' orphaned records');

        foreach ($records as $record) {
            $DB->delete_records('local_a11y_check_type_pdf', ['scanid' => $record->scanid]);
            $DB->delete_records('local_a11y_check_queue', ['id' => $record->scanid]);
            $DB->delete_records('local_a11y_check_pivot',
                ['scanid' => $record->scanid, 'fileid' => $record->fileid, 'courseid' => $record->courseid]);
        }

    }


    /**
     * Create a tmp file in the Moodle temp file storage area and return the path.
     * @param $file
     * @return string
     */
    private static function create_tmp_file($file): string {
        global $CFG;
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($file->fileid);
        $content = $file->get_content();
        // Copy the file to a temp directory so that it can be scanned.
        $tmpfile = $CFG->dataroot . '/temp/filestorage/' . $file->get_pathnamehash() . '.pdf';
        file_put_contents($tmpfile, $content);
        return $tmpfile;
    }

    /**
     * Scan queued files (at random) and returns its accessibility results,
     * @return void
     */
    public static function scan_queued_files(): void {

        $files = self::find_unscanned_files();

        // Get a random subset of 2 from $files to scan.
        $keys = array_rand($files, 2);

        if (count($files) == 0) {
            mtrace('No files found');
            return;
        }

        foreach ($keys as $key) {
            $fileref = $files[$key];
            $tmpfile = self::create_tmp_file($fileref);
            $results = \local_a11y_check\pdf_scanner::scan($tmpfile);
            var_dump($results);
            // Make sure to delete tmpfile!
            unlink($tmpfile);
        }

    }

    public static function find_unscanned_files(): array {
        global $DB;

        $sql = <<< 'SQL'
            select *
            from {local_a11y_check_queue} "lacq"
            inner join {local_a11y_check_pivot} "lacp" on lacp.scanid = lacq.id
            inner join {files} "f" on f.id = lacp.fileid
            where lacq.status = 0 
            limit 5
        SQL;

        $files = [];
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $files[] = $record;
        }
        $recordset->close();
        return $files;
    }

    /**
     * Updates the scan record for file
     * @param string $contenthash
     * @param \result $payload The results of the a11y scan
     * @return boolean
     */
    public static function update_scan_record($contenthash, $payload) {

        global $DB;

        // Create the query.
        $sql = "UPDATE {local_a11y_check_type_pdf}\n"
            . "SET hastext={$payload->hastext},"
            . "hastitle={$payload->hastitle},"
            . "haslanguage={$payload->haslanguage},"
            . "hasbookmarks={$payload->hasbookmarks},"
            . "istagged={$payload->istagged},"
            . "pagecount={$payload->pagecount}\n"
            . "WHERE contenthash='{$contenthash}'";

        // Run the query. This will return true if the query was successful.
        return $DB->execute($sql);
    }

    /**
     * Update scan status for given scanid
     * @param int $scanid
     * @param int $status
     * @param string|null $statustext
     * @return boolean
     */
    public static function update_scan_status($scanid, $status, $statustext = null) {
        global $DB;

        $now = time();
        $sql = "UPDATE {local_a11y_check}\n"
        . "SET status={$status},"
        . "statustext='{$statustext}',"
        . "lastchecked={$now}\n"
        . "WHERE id='{$scanid}'";

        $DB->execute($sql);

        return true;
    }

    /**
     * Checks if a file is less than or greater than the max file size to scan
     * in the plugin settings.
     * @param int $filesize The filesize in megabytes.
     * @return boolean Returns true if the filesize is under the config max file size, and false
     * if it is over it.
     */
    public static function is_under_max_filesize(int $filesize) {
        $maxfilesize = (int) get_config('local_a11y_check', 'max_file_size_mb');
        return (bool) $filesize <= $maxfilesize;
    }


    /**
     * Create a row in the a11y_check_type_pdf table.
     * @param int $id The scan id.
     * @param object $file The file object.
     * @return mixed Returns the created record id on success.
     */
    public static function create_scan_result_record(int $id, $file) {
        global $DB;
        $record = new \stdClass;
        $record->scanid = $id;
        $record->contenthash = $file->contenthash;
        $record->pathnamehash = $file->pathnamehash;
        $record->file_author = $file->author;
        $record->file_timecreated = $file->file_timecreated;
        $record->courseinfo = json_encode($file->courseinfo);
        return $DB->insert_record('local_a11y_check_type_pdf', $record);
    }

    /**
     * Creates the required records in a11y_check and a11y_check_type_pdf tables.
     * @param mixed $file The file to create the records for.
     */
    public static function provision_db_records($file) {
        global $DB;
        $id = self::create_scan_record($file);
        if (!$id) {
            // If a record cannot be created, there's no need to create the scan_result_record.
            mtrace('Could not create scan_record for ' . $file->contenthash);
        } else {
            // If the scan_result_record cannot be created, remove the original record for the database.
            if (!self::create_scan_result_record($id, $file)) {
                mtrace('Could not create scan_result_record for ' . $file->contenthash);
                $DB->delete_records('local_a11y_check', array('id' => $id));
            }
        }
    }

    /**
     * Get the scan status of a file.
     * @param int $scanid The scanid of the file.
     * @param int $limit The limit of records to return. Optional.
     * @return int
     */
    public static function get_scan_status($scanid, $limit = 5) {
        global $DB;
        $sql = "SELECT c.status, c.statustext
            FROM {local_a11y_check} c
            WHERE c.id = {$scanid}
        ";
        $records = $DB->get_records_sql($sql, null, 0, $limit);
        if (!$records) {
            return LOCAL_A11Y_CHECK_STATUS_UNCHECKED;
        } else {
            // Get the first value in the array.
            $record = reset($records);
            return $record->status;
        }
    }

    /**
     * Takes the result object and returns the accessibility status.
     * @param \result $result The result object
     * @return int
     */
    public static function eval_a11y_status($result) {
        if (
            boolval($result->hastext)
            && boolval($result->istagged)
            && boolval($result->hastitle)
            && boolval($result->haslanguage)
        ) {
            return LOCAL_A11Y_CHECK_STATUS_PASS;
        } else if (!boolval($result->hastext)) {
            return LOCAL_A11Y_CHECK_STATUS_CHECK;
        } else {
            return LOCAL_A11Y_CHECK_STATUS_FAIL;
        }
    }

}
