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
 * local_a11y_check unit tests
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Local a11y scan unit test.
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_a11y_assert_pdf_local_scan_testcase extends advanced_testcase {

    public function test_pdf_local_scan() {
        $this->resetAfterTest(true);
        $dir = dirname(__FILE_) . '/pdf';
        $testfiles = array();

        if ($is_dir(dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach($files as $file) {
                $fp = $dir . '/' . $file;
                $ext = pathinfo($fp, PATHINFO_EXTENSION);
                if ($ext === 'pdf') {
                    array_push($testfiles, $fp);
                }
            }
        }

        foreach($testfiles as $pdf) {
            $file = basename($pdf);
            $filename = explode('.pdf', $file)[0];

            if (strpos($filename, '-') !== false) {
                $a11ytokens = explode('-', explode('_', $filename)[1]);
                $a11ystatus = new \stdClass;
                $contents = file_get_contents($pdf);
                $results = \local_a11y_check\scanner::scan($contents);
                if (in_array('TI', $a11ytokens)) {
                    $a11ystatus->hastitle = 1;
                }
                
                if (in_array('OL', $a11ytokens)) {
                    $a11ystatus->hasoutline = 1;
                }

                if (in_array('LN', $a11ytokens)) {
                    $a11ystatus->haslanguage = 1;
                }
                
                if (in_array('TX', $a11ytokens)) {
                    $a11ystatus->hastext = 1;
                }

                $this->assertEquals($a11ystatus, $results);
            }
        }

    }
}
