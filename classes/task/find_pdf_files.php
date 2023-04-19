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
class find_pdf_files extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string the name of the task
     */
    public function get_name() {
        return get_string('pdf:find_files_task', 'local_a11y_check');
    }

    /**
     * Find unscanned PDF files in the Moodle file system.
     */
    public function execute() {

        // Set the timeout in seconds.
        $timeout = 10;

        // Get the max amount of files to process from the plugin config.
        $limit = (int) get_config('local_a11y_check', 'files_per_cron');

        // Get the unscanned PDF files.
        $files = \local_a11y_check\pdf::get_unqueued_files();

        // Only process if there are files to process.
        if (is_array($files) && count($files) > 0) {
            $lockfactory = \core\lock\lock_config::get_lock_factory('local_a11y_check_find_pdf_files_task');
            foreach ($files as $file) {
                $lockkey = "fileid: {$file->fileid}";
                if ($lock = $lockfactory->get_lock($lockkey, $timeout)) {
                    \local_a11y_check\pdf::put_file_in_queue($file);
                    $lock->release();
                } else {
                    throw new \moodle_exception('locktimeout');
                }
            }
        }
    }
}
