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

        $sql = "SELECT f.contenthash
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
        return $files;
    }

    /**
     * Create the scan and result record for a single PDF.
     * @param string $contenthash The contenthash for a PDF
     *
     * @return boolean
     */
    public static function create_scan_record(string $contenthash) {
        global $DB;

        // Set status.
        $status = LOCAL_A11Y_CHECK_TYPE_PDF;

        // Create the primary scan record.
        $scanrecord              = new \stdClass;
        $scanrecord->checktype   = $status;
        $scanrecord->faildelay   = 0;
        $scanrecord->lastchecked = 0;
        $scanrecord->status      = LOCAL_A11Y_CHECK_STATUS_UNCHECKED;
        $scanid                  = $DB->insert_record('local_a11y_check', $scanrecord);

        if (!$scanid) {
            mtrace("Failed to insert scan record for PDF {$contenthash}");
            return false;
        }

        // Create the scan result record.
        $scanresult              = new \stdClass;
        $scanresult->scanid      = $scanid;
        $scanresult->contenthash = $contenthash;
        $scanresultid            = $DB->insert_record('local_a11y_check_type_pdf', $scanresult);

        if (!$scanresultid) {
            mtrace("Failed to insert scan result record for PDF {$contenthash}");
            $DB->delete_records('local_a11y_check', array('id' => $scanid));
            return false;
        }

        return true;
    }

    /**
     * @description This function will GET the presigned URL from AWS that will allow us to post the file
     * @params String $url
     * @returns StdClass
     */
    public static function get_presigned_url(string $url, string $apikey) {

        $ch = curl_init($url);

        $headers = array(
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            'x-api-key: ' . $apikey
        );

        $opts = array(
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
        );

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $error = curl_error($ch);
            return false;
        }

        curl_close($ch);

        $json = json_decode($res);

        $response = new stdClass();
        $response->uploadURL = $json->uploadURL;
        $response->key = $json->key;

        return $response;
    }

    /**
     * @description This function will put the file into
     * an AWS S3 bucket
     * @param String url 
     * @param String key
     * @param String file
     * @returns Boolean
     */
    public static function put_file(string $url, string $key, string $file) {
        $ch = curl_init($url);
        $size = filesize($file);

        $headers = array(
            'filename: ' . $key,
            'Content-Length: ' . $size
        );

        $opts = array(
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $file,
            CURLOPT_INFILESIZE => $size,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 60,
        );

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $error = curl_error($ch);
            return false;
        }

        curl_close($ch);

        return true;
    }

    /**
     * @description This function will trigger a lambda function in
     * AWS
     * @params String $url
     * @params String $key
     * @returns StdClass
     */
    public static function scan_file(string $url, string $key) {
        $headers = array(
            'Content-Type: application/json',
            'x-api-key: ' . $key
        );
        $body = json_encode(array( 'key' => $key ));

        $opts = array(
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 60,
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $error = curl_error($ch);
            return false;
        }

        curl_close($ch);

        $json = json_decode($res);

        return $json;
    }

}