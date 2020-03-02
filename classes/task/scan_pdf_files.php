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

        if (!$apiBaseURL) {
            mtrace("API Base URL setting is missing!");
            die();
        }

        if (!$apiToken) {
            mtrace("API token setting is missing!");
            die();
        }

        $files = \local_a11y_check\pdf::get_pdf_files();
        $fs = get_file_storage();

        if (!is_array($files) || count($files) <= 0) {
            die();
        }
        
        $requestHandler = new \local_a11y_check\lambdascan($apiBaseURL, $apiToken);

        // Get the credentials
        $credentials = $requestHandler->getPresignedURL('/test/requesturl');

        foreach ($files as $ref) {
          
          $file = $fs->get_file_by_hash($ref->pathnamehash);
          $contenthash = $ref->contenthash;
          $scanid = $ref->scanid;
          $fh = $file->get_content_file_handle();
          $put_response = $requestHandler->putFile($credentials->uploadURL, $credentials->key, $fh);
          $scan_response = $requestHandler->scanFile('/test/scan', $credentials->key);
          
          // to whoever sees this monstrosity of an error check, i am sorry 
          if (property_exists($scan_response, "message")) {
            if ($scan_response->message === "Internal server error") {
              mtrace("Skipping file");
              continue;
            }
          }

          $payload = new \stdClass();
          $payload->hastext = $scan_response->hasText ? 1 : 0;
          $payload->hastitle = $scan_response->title ? 1 : 0;
          $payload->haslanguage = $scan_response->language ? 1 : 0;
          $payload->hasoutline = $scan_response->hasOutline ? 1 : 0;

          // update the results
          $updated_record = \local_a11y_check\pdf::update_scan_record($contenthash, $payload);

        }
    
    }
}