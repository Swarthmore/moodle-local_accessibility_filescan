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
 * Scan pdfs for a11y using a remote instance of https://github.com/Swarthmore/filescan-server
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

/**
 * A class to orchestrate the scanning of a pdf for a11y
 */
class remotescan {
    /**
     * Scan a pdf for a11y using a web endpoint
     * @param string $endpoint The endpoint
     * @param stored_file $file The file to scan
     * @param string $contenthash The contenthash of the file
     * @return array
     */
    public static function scan($endpoint, $file, $contenthash) {
        // See https://github.com/moodle/moodle/blob/master/lib/filelib.php#L2972.
        $request = new \curl();

        $headers = array(
            "cache-control: no-cache",
            "Content-Type: multipart/form-data",
            "Accept: application/json"
        );

        $opts = array(
            "curlopt_httpheader" => $headers,
            "curlopt_timeout" => "120L",
            "curlopt_followlocation" => true,
            "curlopt_header" => false
        );

        $params = array(
            "upfile" => $file,
            "id" => $contenthash
        );

        $response = $request->post($endpoint, $params, $opts);

        $info = $request->get_info();

        if ($info["http_code"] != 200) {
            throw new \runtimeexception("Bad status code from filescan server. Received: " . $info["http_code"]);
        } else {
            $results = json_decode($response, true);
            return $results;
        }
    }
}
