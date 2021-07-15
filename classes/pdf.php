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
 * PDF helper functions
 */
class pdf {

    /**
     * Get all PDF files.
     * @param int $limit The number of files to process at a time.
     * @return array
     */
    public static function get_all_pdfs($limit = 100000) {
        global $DB;
        $sql = "SELECT f.contenthash, f.pathnamehash, MAX(f.filesize) as filesize
            FROM {files} f
                INNER JOIN {context} c ON c.id=f.contextid
                WHERE c.contextlevel = 70
                AND f.filesize <> 0
                AND f.mimetype = 'application/pdf'
                AND f.component <> 'assignfeedback_editpdf'
                AND f.filearea <> 'stamps'
            GROUP BY f.contenthash, f.pathnamehash
            ORDER BY MAX(f.id) DESC";
        $files = $DB->get_records_sql($sql, null, 0, $limit);
        !$files ? mtrace("No PDF files found") : "Found " . count($files) . " PDF files";
        return $files;
    }

    /**
     * Get all of the a11y_check records.
     * @return int $limit
     */
    public static function get_all_records($limit = 100000) {
        global $DB;
        $sql = "SELECT tp.contenthash as contenthash, c.id as scanid
            FROM {local_a11y_check_type_pdf} tp
            INNER JOIN {local_a11y_check} c ON c.id = tp.scanid";
        $records = $DB->get_records_sql($sql, null, 0, $limit);
        return $records;
    }

    /**
     * Get all of the rows in local_a11y_check_type_pdf that should be deleted.
     */
    public static function get_rows_to_delete($limit = 100000) {
        global $DB;
        $records = self::get_all_records($limit);
    }

    /**
     * Get all unscanned PDF files.
     * @param int $limit The number of files to process at a time.
     * @return array
     */
    public static function get_unscanned_pdf_files($limit = 1000) {
        global $DB;

        mtrace("Looking for PDF files to scan for accessibility");
        $sql = "SELECT f.contenthash, f.pathnamehash, MAX(f.filesize) as filesize
            FROM {files} f
                INNER JOIN {context} c ON c.id=f.contextid
                LEFT OUTER JOIN {local_a11y_check_type_pdf} actp ON f.contenthash=actp.contenthash
                WHERE c.contextlevel = 70
                AND f.filesize <> 0
                AND f.mimetype = 'application/pdf'
                AND f.component <> 'assignfeedback_editpdf'
                AND f.filearea <> 'stamps'
                AND actp.contenthash IS NULL
            GROUP BY f.contenthash, f.pathnamehash
            ORDER BY MAX(f.id) DESC";

        $files = $DB->get_records_sql($sql, null, 0, $limit);
        !$files ? mtrace("No PDF files found") : "Found " . count($files) . " PDF files";
        return $files;
    }

    /**
     * Get files that have been scanned, but do not have anything in the
     * mdl_local_a11y_check_type_pdf table
     * @param int $limit
     * @return array
     */
    public static function get_pdf_files($limit = 10000) {

        global $DB;

        $sql = "SELECT f.scanid, f.contenthash as contenthash, f.pathnamehash as pathnamehash
            FROM {local_a11y_check_type_pdf} f
            INNER JOIN {local_a11y_check} c ON c.id = f.scanid
        ";

        $files = $DB->get_records_sql($sql, null, 0, $limit);
        !$files ? mtrace("No PDF files found") : mtrace("Found " . count($files) . " PDF files");

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

        $sql = "UPDATE {local_a11y_check_type_pdf}\n"
            . "SET hastext={$payload->hastext},"
            . "hastitle={$payload->hastitle},"
            . "haslanguage={$payload->haslanguage},"
            . "hasbookmarks={$payload->hasbookmarks},"
            . "istagged={$payload->istagged},"
            . "pagecount={$payload->pagecount}\n"
            . "WHERE contenthash='{$contenthash}'";

        $DB->execute($sql);

        return true;
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
     * Create the scan and result record for a single PDF.
     * @param \stdClass $file The partial SQL file record containing contenthash and filesize
     * @return boolean
     */
    public static function create_scan_record($file) {
        global $DB;

        // Create the primary scan record for the PDF file.
        $scanrecord = new \stdClass;
        $scanrecord->checktype = LOCAL_A11Y_CHECK_TYPE_PDF;
        $scanrecord->faildelay = 0;
        $scanrecord->lastchecked = 0;

        // Determine if PDF is too big to scan.
        // Moodle file sizes are stored as bytes in the database.
        // Max file size setting is in megabytes (MB).
        $maxfilesize = (int) get_config("local_a11y_check", "max_file_size_mb");
        if ($file->filesize > $maxfilesize * 1000000) {
            // File is too big, ignore.
            $scanrecord->status = LOCAL_A11Y_CHECK_STATUS_IGNORE;
            $scanrecord->statustext = "File too large to scan";
        } else {
            $scanrecord->status = LOCAL_A11Y_CHECK_STATUS_UNCHECKED;
        }

        $scanid = $DB->insert_record('local_a11y_check', $scanrecord);

        if (!$scanid) {
            mtrace("Failed to insert scan record for PDF {$contenthash}");
            return false;
        }

        // Create the scan result record.
        $scanresult = new \stdClass;
        $scanresult->scanid = $scanid;
        $scanresult->contenthash = $file->contenthash;
        $scanresult->pathnamehash = $file->pathnamehash;
        $scanresultid = $DB->insert_record('local_a11y_check_type_pdf', $scanresult);

        if (!$scanresultid) {
            mtrace("Failed to insert scan result record for PDF {$contenthash}");
            $DB->delete_records('local_a11y_check', array('id' => $scanid));
            return false;
        }

        return true;
    }

    /**
     * Get the scan status of a file.
     * @param int $scanid The scanid of the file.
     * @param int $limit The limit of records to return. Optional.
     * @return int
     */
    public static function get_scan_status($scanid, $limit = 5000) {
        global $DB;
        $sql = "SELECT c.status, c.statustext
            FROM {local_a11y_check} c
            WHERE c.id = {$scanid}
        ";
        $records = $DB->get_records_sql($sql, null, 0, $limit);
        if (!$records) {
            mtrace("No scan records found for id " . $scanid);
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
