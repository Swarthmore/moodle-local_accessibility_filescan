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
    public function get_unscanned_pdf_files($limit = 1000) {
        global $DB;

        $sql = 'SELECT distinct f.contenthash
        FROM {files} f INNER JOIN {context} c ON c.id=f.contextid LEFT OUTER JOIN {local_a11y_check_type_pdf} actp ON f.contenthash=actp.contenthash
            WHERE c.contextlevel = 70
            AND f.filesize <> 0
            AND f.mimetype = "application/pdf"
            AND f.component <> "assignfeedback_editpdf"
            AND f.filearea <> "stamps"
            ORDER BY f.id DESC';

        $files = $DB->get_records_sql($sql, null, 0, $limit);
        return $files;
    }

    /**
     * Create the scan and result record for a single PDF.
     * @param string $contenthash The contenthash for a PDF
     *
     * @return boolean
     */
    public function create_scan_record(string $contenthash) {
        global $DB;

        // Create the primary scan record.
        $scanrecord              = new stdClass;
        $scanrecord->checktype   = LOCAL_A11Y_CHECK_TYPE_PDF;
        $scanrecord->faildelay   = 0;
        $scanrecord->lastchecked = 0;
        $scanrecord->status      = LOCAL_A11Y_CHECK_STATUS_UNCHECKED;
        $scanid                  = $DB->insert_record('local_a11y_check', $scanrecord);

        if (!$scanid) {
            return false;
        }

        // Create the scan result record.
        $scanresult              = new stdClass;
        $scanresult->scanid      = $scanid;
        $scanresult->contenthash = $contenthash;
        $scanresultid            = $DB->insert_record('local_a11y_check_type_pdf', $scanresult);

        if (!$scanresultid) {
            $DB->delete_records('local_a11y_check', array('id' => $scanid));
            return false;
        }

        return true;
    }
}