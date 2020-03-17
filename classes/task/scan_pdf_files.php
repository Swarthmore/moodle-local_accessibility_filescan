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

    public function execute() {

        $pluginconfig = get_config('local_a11y_check');

        $apibaseurl = $pluginconfig->api_url;
        $apitoken = $pluginconfig->api_token;
        $maxfilesize = $pluginconfig->max_file_size_mb;

        if (!$apibaseurl) {
            mtrace("API Base URL setting is missing!");
            die();
        }

        if (!$apitoken) {
            mtrace("API token setting is missing!");
            die();
        }

        $files = \local_a11y_check\pdf::get_pdf_files();
        $fs = get_file_storage();

        if (!is_array($files) || count($files) <= 0) {
            die();
        }

        $requesthandler = new \local_a11y_check\lambdascan($apibaseurl, $apitoken);

        $credentials = $requesthandler->getpresignedurl('/test/requesturl');

        foreach ($files as $ref) {

            $file = $fs->get_file_by_hash($ref->pathnamehash);
            $contenthash = $ref->contenthash;
            $scanid = $ref->scanid;
            $fh = $file->get_content_file_handle();
            $putresponse = $requesthandler->putfile($credentials->uploadURL, $credentials->key, $fh);
            $scanresponse = $requesthandler->scanfile('/test/scan', $credentials->key);

            if (property_exists($scanresponse, "message")) {
                if ($scanresponse->message === "Internal server error") {
                    mtrace("Skipping file");
                    continue;
                }
            }

            $payload = new \stdClass();
            $payload->hastext = $scanresponse->hasText ? 1 : 0;
            $payload->hastitle = $scanresponse->title ? 1 : 0;
            $payload->haslanguage = $scanresponse->language ? 1 : 0;
            $payload->hasoutline = $scanresponse->hasOutline ? 1 : 0;

            $updatedrecord = \local_a11y_check\pdf::update_scan_record($contenthash, $payload);

        }

    }
}