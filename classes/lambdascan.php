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
 * Manage a file scan request to AWS lambda
 *
 * @package local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

class lambdascan {

    function __construct($apiBaseURL, $apikey) {
        $this->apiBaseURL = $apiBaseURL;
        $this->apikey = $apikey;
    }

    private function handleError($error) {
        var_dump($error);
    }

    /**
     * @description This function will GET the presigned URL from AWS that will allow us to post the file
     * @params String $url
     * @returns StdClass 
     */
    public function getPresignedURL($url) {

        $curl_url = $this->apiBaseURL . $url;
        $ch = curl_init($curl_url);
        
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
            $this->handleError($error);
            curl_close($ch);
            return;
        }

        $json = json_decode($res);
        
        $returnVals = new \stdClass();
        $returnVals->uploadURL = $json->uploadURL;
        $returnVals->key = $json->key;

        return $returnVals;
    }

    /**
     * @description This function will put the file into
     * an AWS S3 bucket
     * @param String presignedURL
     * @param String key
     * @param Resource fh
     * @returns Boolean
     */
    public function putFile($url, $key, $fh) {
        $ch = curl_init($url);

        $fstats = fstat($fh);
        $file_size = $fstats["size"];

        $headers = [
          'filename: ' . $key,
          'Content-Length: ' . $file_size
        ];

        $opts = [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $file_size,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 60,
        ];

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        // Handle errors
        if (curl_error($ch)) {
            $error = curl_error($ch);
            $this->handleError($error);
        }

        curl_close($ch);

        return true;
    }

    /**
     * @description This function will trigger a lambda function
     * @param String $url
     * @param String $key
     * @return StdClass
     */
    public function scanFile($url, $key) {
        $curl_url = $this->apiBaseURL . $url;
        $headers = [
            'Content-Type: application/json'
        ];
        $body = json_encode([ 'key' => $key ]);

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60, // time out on conect
            CURLOPT_TIMEOUT => 60, // time out on response
        ];

        $ch = curl_init($curl_url);

        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);

        // Handle errors
        if (curl_error($ch)) {
            $error = curl_error($ch);
            $this->handleError($error);
        }

        curl_close($ch);

        $json = json_decode($res);

        return $json;
    }

}