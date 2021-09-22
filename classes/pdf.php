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
require_once(dirname(__FILE__) . '/helpers.php');

/**
 * PDF helper functions
 */
class pdf {

    /**
     * Remove all rows that that don't have a record in mdl_files (draft context is ignored).
     * @param int $limit The number of files to process at a time.
     * @return bool
     */
    public static function remove_deleted_files($limit = 5) {
        global $DB;
        // Get all records with a contenthash that exists in the plugin, but does not exist in the mdl_files table.
        // This indicates that the file was deleted.
        // TODO: Someone should check this -- I'm tired and not sure if this is the right way to do it.
        $sql = "SELECT tp.contenthash, tp.scanid
                FROM {local_a11y_check_type_pdf} tp
                INNER JOIN {local_a11y_check} c ON c.id = tp.scanid
                WHERE tp.contenthash NOT IN (
                    SELECT f.contenthash
                    FROM {files} f
                    WHERE f.contenthash = tp.contenthash AND f.filearea <> 'draft'
                )";
        $records = $DB->get_records_sql($sql, null, 0, $limit);
        // Iterate over the $todelete records and delete them from the database.
        foreach ($records as $row) {
            $DB->delete_records('local_a11y_check_type_pdf', array('contenthash' => $row->contenthash, 'scanid' => $row->scanid));
            $DB->delete_records('local_a11y_check', array('id' => $row->scanid));
        }
        return true;
    }

    /**
     * Get all PDFs that have not been scanned by this plugin.
     * @param int $limit The number of files to process at a time.
     * @return array
     */
    public static function get_unscanned_pdf_files($limit = 5) {

        global $DB;

        // Create the query.
        $sql = "SELECT f.contenthash as contenthash, f.pathnamehash as pathnamehash,
            f.id as file_id,
            f.author as author,
            f.timecreated as file_timecreated,
            MAX(f.filesize) as filesize,
            crs.id as course_id,
            crs.category as course_category,
            crs.fullname as course_name,
            crs.shortname as course_shortname,
            crs.startdate as course_start,
            crs.enddate as course_end
            FROM {files} f
                INNER JOIN {context} ctx ON ctx.id=f.contextid
                LEFT OUTER JOIN {course} crs ON crs.id=ctx.instanceid
                LEFT OUTER JOIN {local_a11y_check_type_pdf} actp ON f.contenthash=actp.contenthash
                WHERE ctx.contextlevel = 70
                AND f.filesize <> 0
                AND f.mimetype = 'application/pdf'
                AND f.component <> 'assignfeedback_editpdf'
                AND f.filearea <> 'stamps'
                AND actp.contenthash IS NULL
            GROUP BY f.contenthash, f.pathnamehash,
                     f.id, f.author, f.timecreated, crs.id, crs.category, crs.fullname, crs.shortname,
                     crs.startdate, crs.enddate
            ORDER BY MAX(f.filesize) DESC";

        // Run the query.
        $files = $DB->get_records_sql($sql, null, 0, $limit);

        // Iterate through each of the files and get the instructors.
        foreach ($files as $file) {

            // First check to make sure the course id exists.
            if ($file->course_id) {
                // Get the instructors for the course.
                $instructors = \local_a11y_check\halp::get_instructors_for_course($file->course_id);
                // Create the courseinfo object.
                $courseinfo = new \local_a11y_check\courseinfo(
                    $file->course_id,
                    $file->course_category,
                    $file->course_name,
                    $file->course_shortname,
                    $file->course_start,
                    $file->course_end,
                    $instructors
                );
                // Add the courseinfo object to the file object.
                $file->courseinfo = $courseinfo;
            } else {
                // Create a placeholder for the courseinfo.
                $file->courseinfo = json_encode((object) array());
            }

        }

        // Return the files.
        return $files;

    }

    /**
     * Get files that have been scanned, but do not have anything in the
     * mdl_local_a11y_check_type_pdf table
     * @param int $limit
     * @return array
     */
    public static function get_pdf_files($limit = 5) {

        global $DB;

        // Create the query.
        $sql = "SELECT f.scanid, f.contenthash as contenthash, f.pathnamehash as pathnamehash
            FROM {local_a11y_check_type_pdf} f
            INNER JOIN {local_a11y_check} c ON c.id = f.scanid
            WHERE c.status = " . LOCAL_A11Y_CHECK_STATUS_UNCHECKED;

        // Run the query.
        $files = $DB->get_records_sql($sql, null, 0, $limit);

        // Return the files.
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
     * Create the scan and result record for a single PDF.
     * @param mixed $file The partial SQL file record containing contenthash and filesize
     * @return mixed Returns the created record id on success.
     */
    public static function create_scan_record($file) {
        global $DB;

        // Create the primary scan record for the PDF file.
        $record  = new \stdClass;
        $record->checktype = LOCAL_A11Y_CHECK_TYPE_PDF;
        $record->faildelay = 0;
        $record->lastchecked = 0;

        // Determine if PDF is too big to scan.
        if (self::is_under_max_filesize($file->filesize)) {
            $record->status = LOCAL_A11Y_CHECK_STATUS_UNCHECKED;
        } else {
            // File is too big, ignore.
            $record->status = LOCAL_A11Y_CHECK_STATUS_IGNORE;
            $record->statustext = "File too large to scan";
        }

        // Insert the scan record into the database.
        return $DB->insert_record('local_a11y_check', $record);
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
