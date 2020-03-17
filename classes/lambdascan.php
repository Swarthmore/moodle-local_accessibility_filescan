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
 * one line description of file
 * @package local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * This class Manage a file scan request to AWS lambda
 * @copyright 2020 Swarthmore College
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lambdascan {

    /**
     * Creates an instance of the lambdascan class
     * @param String $apibaseurl - The base url to the your lambda func
     * @param String $apikey - The api key to access your lamba func
     */
    protected function __construct($apibaseurl, $apikey) {
        $this->apiBaseURL = $apibaseurl;
        $this->apikey = $apikey;
    }

    /**
     * Handles errors
     * @param * $error
     */
    private function handleerror($error) {
        var_dump($error);
    }

    /**
     * This function will GET the presigned URL from AWS that will allow us to post the file
     * @param String $url
     * @return StdClass
     */
    public function getpresignedurl($url) {

        $curlurl = $this->apiBaseURL . $url;
        $ch = curl_init($curlurl);

        $headers = [
            'Content-Type: application/json',
            'Connection: Keep-Alive',
        ];

        $opts = [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120
        ];

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $error = curl_error($ch);
            $this->handleerror($error);
            curl_close($ch);
            return;
        }

        $json = json_decode($res);

        $returnvals = new \stdClass();
        $returnvals ->uploadURL = $json->uploadURL;
        $returnvals ->key = $json->key;

        return $returnvals;
    }

    /**
     * This function will put the file into an s3 bucket
     * @param String $url
     * @param String $key
     * @param Resource $fh
     * @return Boolean
     */
    public function putfile($url, $key, $fh) {
        $ch = curl_init($url);

        $fstats = fstat($fh);
        $filesize = $fstats["size"];

        $headers = [
          'filename: ' . $key,
          'Content-Length: ' . $filesize
        ];

        $opts = [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $filesize,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 60,
        ];

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $error = curl_error($ch);
            $this->handleerror($error);
        }

        curl_close($ch);

        return true;
    }

    /**
     * This function will trigger a lambda function
     * @param String $url
     * @param String $key
     * @return StdClass
     */
    public function scanfile($url, $key) {
        $curlurl = $this->apiBaseURL . $url;
        $headers = [
            'Content-Type: application/json'
        ];
        $body = json_encode([ 'key' => $key ]);

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 60,
        ];

        $ch = curl_init($curlurl);

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $error = curl_error($ch);
            $this->handleerror($error);
        }

        curl_close($ch);

        $json = json_decode($res);

        return $json;
    }

}