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
 * Scan validity unit test.
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_a11y_assert_scan_validity_testcase extends advanced_testcase {

    public function test_scan_validity() {
        $pdfdir = dirname(__FILE__) . '/fixtures/pdfs';

        if (!is_dir($pdfdir)) {
            throw new \Exception("Invalid directory.");
        }

        $pdfs = array();
        $files = array_diff(scandir($pdfdir), array('.', '..'));

        foreach ($files as $file) {
            $fp = $pdfdir . '/' . $file;
            $ext = pathinfo($fp, PATHINFO_EXTENSION);
            if ($ext === 'pdf') {
                array_push($pdfs, $fp);
            }
        }

        // For each pdf, extract it's a11y status based on the filename.
        foreach ($pdfs as $pdf) {
            $file = basename($pdf);
            $filename = explode('.pdf', $file)[0];

            if (strpos($filename, '-') !== false) {

                $a11ytokens = explode('-', explode('_', $filename)[1]);
                $a11ycheck = new \local_a11y_check\pdf_a11y_results();
                $contents = file_get_contents($pdf);
                $results = \local_a11y_check\pdf_scanner::scan($pdf);

                if (in_array('TI', $a11ytokens)) {
                    $a11ycheck->hastitle = 1;
                }

                if (in_array('OL', $a11ytokens)) {
                    $a11ycheck->hasbookmarks = 1;
                }

                if (in_array('LN', $a11ytokens)) {
                    $a11ycheck->haslanguage = 1;
                }

                if (in_array('TX', $a11ytokens)) {
                    $a11ycheck->hastext = 1;
                }

                $this->assertEquals($a11ycheck->hastitle, $results->hastitle);
                $this->assertEquals($a11ycheck->hasbookmarks, $results->hasbookmarks);
                $this->assertEquals($a11ycheck->haslanguage, $results->haslanguage);
                $this->assertEquals($a11ycheck->hastext, $results->hastext);
            }
        }
    }

}

