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
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * PDF helper functions local_a11y_check
 *
 * @package   local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdf {
    /**
     * Get all unscanned PDF files.
     * @param int $limit The number of files to process at a time.
     *
     * @return array
     */
    public static function get_unscanned_pdf_files($limit = 1000) {
        global $DB;

        mtrace("Looking for PDF files to scan for accessibility");
        $sql = "SELECT f.contenthash, f.pathnamehash as pathnamehash, MAX(f.filesize) as filesize
            FROM {files} f
                INNER JOIN {context} c ON c.id=f.contextid
                LEFT OUTER JOIN {local_a11y_check_type_pdf} actp ON f.contenthash=actp.contenthash
                WHERE c.contextlevel = 70
                AND f.filesize <> 0
                AND f.mimetype = 'application/pdf'
                AND f.component <> 'assignfeedback_editpdf'
                AND f.filearea <> 'stamps'
                AND actp.contenthash IS NULL
            GROUP BY f.contenthash
            ORDER BY MAX(f.id) DESC";

        $files = $DB->get_records_sql($sql, null, 0, $limit);
        if (!$files) {
            mtrace("No PDF files found");
        } else {
            mtrace("Found " . count($files) . " PDF files");
        }
        return $files;
    }

    /**
     * Get files that have been scanned, but do not have anything in the
     * mdl_local_a11y_check_type_pdf table
     *
     * @return array
     */
    public static function get_pdf_files($limit = 10000) {

        global $DB;

        $sql = "SELECT f.contenthash as contenthash, f.pathnamehash as pathnamehash
            FROM {local_a11y_check_type_pdf} f
            INNER JOIN {local_a11y_check} c ON c.id = f.scanid
        ";

        $files = $DB->get_records_sql($sql, null, 0, $limit);

        if (!$files) {
            mtrace("No PDF files found");
        } else {
            mtrace("Found " . count($files) . " PDF files");
        }

        return $files;
    }

    /**
     * Updates the scan record for file
     *
     * @notes
     * the payload should have these properties
     * it should be a class..  TODO
     * $payload->hastext
     * $payload->hastitle
     * $payload->haslanguage
     * $payload->hasoutline
     */
    public static function update_scan_record($contenthash, $payload) {
        global $DB;

        $table = "mdl_local_a11y_check_type_pdf";
        $sql = "UPDATE {$table}
            SET hastext={$payload->hastext},hastitle={$payload->hastitle},haslanguage={$payload->haslanguage},hasoutline={$payload->hasoutline}
            WHERE contenthash = '{$contenthash}'
        ";
        $DB->execute($sql);

        return true;
    }

    /**
     * Create the scan and result record for a single PDF.
     * @param stdClass $file The partial SQL file record containing contenthash and filesize
     *
     * @return boolean
     */
    public static function create_scan_record($file) {
        global $DB;

        // Create the primary scan record for the PDF file.
        $scanrecord              = new \stdClass;
        $scanrecord->checktype   = LOCAL_A11Y_CHECK_TYPE_PDF;
        $scanrecord->faildelay   = 0;
        $scanrecord->lastchecked = 0;

        // Determine if PDF is too big to scan.
        // Moodle file sizes are stored as bytes in the database
        // Max file size setting is in megabytes (MB).
        $maxfilesize = (int) get_config("local_a11y_check", "max_file_size_mb");
        if ($file->filesize > $maxfilesize * 1000000) {
            // File is too big, ignore.
            $scanrecord->status      = LOCAL_A11Y_CHECK_STATUS_IGNORE;
            $scanrecord->statustext  = "File too large to scan";
        } else {
            $scanrecord->status      = LOCAL_A11Y_CHECK_STATUS_UNCHECKED;
        }

        $scanid = $DB->insert_record('local_a11y_check', $scanrecord);

        if (!$scanid) {
            mtrace("Failed to insert scan record for PDF {$contenthash}");
            return false;
        }

        // Create the scan result record.
        $scanresult               = new \stdClass;
        $scanresult->scanid       = $scanid;
        $scanresult->contenthash  = $file->contenthash;
        $scanresult->pathnamehash = $file->pathnamehash;
        $scanresultid             = $DB->insert_record('local_a11y_check_type_pdf', $scanresult);

        if (!$scanresultid) {
            mtrace("Failed to insert scan result record for PDF {$contenthash}");
            $DB->delete_records('local_a11y_check', array('id' => $scanid));
            return false;
        }

        return true;
    }

    /**
     * Takes the result object and returns the accessibility status.
     * @param \stdClass $result The result object
     *
     * @return int the status
     */
    public static function evaluate_item_status($result) {
        if ($result->title && $result->hasOutline && $result->hasText && $result->language) {
            return LOCAL_A11Y_CHECK_STATUS_PASS;
        } else if ($result->hasText) {
            return LOCAL_A11Y_CHECK_STATUS_CHECK;
        } else {
            return LOCAL_A11Y_CHECK_STATUS_FAIL;
        }
    }
}