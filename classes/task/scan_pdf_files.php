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
 * Find PDF files task definition for local_a11y_check
 *
 * @package   local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_a11y_check\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to find unscanned PDF files.
 *
 * @package   local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scan_pdf_files extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string the name of the task
     */
    public function get_name() {
        return get_string('pdf:scan_files_task', 'local_a11y_check');
    }

    /**
     * @decsription
     */
    public function execute() {

        $pluginConfig = get_config('local_a11y_check');

        $apiBaseURL= $pluginConfig->api_url;
        $apiToken = $pluginConfig->api_token;
        $maxFilesize = $pluginConfig->max_file_size_mb;

        if (!apiBaseURL) {
            mtrace("API Base URL setting is missing!");
            die();
        }

        if (!apiToken) {
            mtrace("API token setting is missing!");
            die();
        }

        $files = \local_a11y_check\pdf::get_pdf_files();
        $fs = get_file_storage();

        if (!is_array($files) || count($files) <= 0) {
            die();
        }
        
        $requestHandler = new \local_a11y_check\lambdascan($apiBaseURL . '/requesturl', $apiToken);

        // just deal with the first file before looping through all
        // of them
        $first = array_values($files)[0];
        $contenthash = $first->contenthash;
        $file = $fs->get_file_by_hash($first->pathnamehash);

        var_dump($file);

        /* $contents = $file->get_content();

        var_dump($contents);

        $size = $file->get_filesize();

        var_dump(size);
            
        $credentials = $requestHandler->getPresignedURL('/requesturl');

        var_dump($credentials);  */

        /* foreach ($files as $f) {

            $file = $fs->get_file_by_hash($f->pathname);
            $fileContents = $file->get_content();
            $fileContentHash = $f->contenthash;
            $fileSize = $file->get_filesize();

            if ((int) $fileSize > (int) $maxFilesize) {
                mtrace('Cannot scan file as it exceeds the max filesize setting.');   
                continue;
            }

            $credentials = $requestHandler->getPresignedURL('/requesturl');

            if ($credentials->statusCode !== 200) {
                mtrace('Received a non-200 response from lambda.');
                continue;
            }

            // TODO: Test if passing the file directly actually works...
            $putResponse = $requestHandler->putFile($credentials->uploadURL, $credentials->key, $file);

            if ($putResponse->statusCode !== 200) {
                mtrace('Received a non-200 response from lambda.');
                continue;
            }

            $scanResponse  = $requestHandler->scanFile('/scan', $credentials->key);

            if ($scanResponse->statusCode !== 200) {
                mtrace('Received a non-200 response from lambda.');
                return false;
            }

            $scanResults = json_decode($scanResponse);

            mtrace($scanResponse);
            mtrace($scanResults);

            // For now, just put the scan id and contenthash there
            \local_a11y_check\pdf::create_scan_record($fileContentHash);

            return true;
        } */
    }
}