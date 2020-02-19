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
      
require_once('./PDF_Scan_Handler.php');

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
        return get_string('task:scan_pdf_files', 'local_a11y_check');
    }
   
    /**
     * @decsription
     */
    public function execute() {
      
      $apiBaseURL = get_config('local_accessibility_check', 'api_url');
      $apiToken = get_config('local_accessibility_check', 'api_token');
      $defaultMaxFileSize = 9999999999;
      $maxFilesize = get_config("local_accessibility_check", "max_file_size_mb") ?: $defaultMaxFileSize;
      
      if (!$apiURL or $apiToken) {
        // TODO: do something other than kill the process
        die();
      }
      
      $files = \local_a11y_check\pdf::get_unscanned_pdf_files();
      $fs = get_files_storage();

      if (!is_array($files) || empty($files)) {
        return false;
      }


      $requestHandler = new PDF_Scan_Handler($apiBaseURL, $apiToken);

      foreach ($files as $f) {

        $file = $fs->get_file_by_hash($f->pathname);
        $fileContents = $file->get_content();
        $fileContentHash = $f->contenthash;
        $fileSize = $file->get_filesize();

        if ((int) $fileSize > (int) $maxFilesize) {
          // TODO: Handle files larger than max filesize 
          continue;
        }

        $credentials = $requestHandler->getPresignedURL('/requesturl');
        
        if ($credentials->statusCode !== 200) {
          // TODO: Handle a bad request
          continue;
        }

        // TODO: Test if passing the file directly actually works...
        $putResponse = $requestHandler->putFile($credentials->uploadURL, $credentials->key, $file);

        if ($putResponse->statusCode !== 200) {
          // TODO: Handle a bad request
          continue;
        }

        $scanResponse  = $requestHandler->scanFile('/scan', $credentials->key);
        if ($scanResponse->statusCode !== 200) {
          // TODO: Handle a bad request
        }

        // TODO: Handle success response
        
        /**
         * A successful response will look like this
         * 
         * {
         * language: false,
         * numPages: 1,
         * metaData: {
         *   PDFFormatVersion: '1.3',
         *   IsLinearized: false,
         *   IsAcroFormPresent: false,
         *   IsXFAPresent: false,
         *   IsCollectionPresent: false,
         *   Producer: 'Mac OS X 10.1.3 Quartz PDFContext',
         *   CreationDate: "D:20020314180735-05'00'"
         * },
         * hasForm: false,
         * title: false,
         * hasOutline: false,
         * hasAttachements: false,
         * hasText: false,
         * pageInfo: [ { pageText: '', pageNum: 1 } ],
         * numPagesChecked: 1
         * }
         */

        // For now, just put the scan id and contenthash there
        \local_a11y_check\pdf::create_scan_record($fileContentHash);
        
        }
    }
}