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

        // Get the directory where all of the PDFs are stored.
        $pdfdir = dirname(__FILE__) . '/fixtures/pdfs';

        if (!is_dir($pdfdir)) {
            throw new \Exception("Invalid directory.");
        }

        // Create an arry to store the pdfs.
        $pdfs = array();

        // Get all of the PDFs in the directory.
        $files = array_diff(scandir($pdfdir), array('.', '..'));

        // Iterate through the files, and if the file is a PDF, add it to $pdfs.
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

            mtrace("Scanning" . $filename);

            if (strpos($filename, '-') !== false) {

                // Extract the a11y tokens from the filename.
                $a11ytokens = explode('-', explode('_', $filename)[1]);

                // Create a blank results object.
                // This will be filled with the results.
                $a11ycheck = new \local_a11y_check\pdf_a11y_results();

                // Get the file contents of the PDF.
                $contents = file_get_contents($pdf);

                // Scan the PDF.
                $results = \local_a11y_check\pdf_scanner::scan($pdf);

                // Set the results based on the filename tokens.
                if (in_array('TI', $a11ytokens)) {
                    $a11ycheck->hastitle = 1;
                }

                if (in_array('OL', $a11ytokens)) {
                    $a11ycheck->istagged = 1;
                }

                if (in_array('LN', $a11ytokens)) {
                    $a11ycheck->haslanguage = 1;
                }

                if (in_array('TX', $a11ytokens)) {
                    $a11ycheck->hastext = 1;
                }

                // Assert that the results match the expected results.
                $this->assertEquals($a11ycheck->hastitle, $results->hastitle);
                $this->assertEquals($a11ycheck->istagged, $results->istagged);
                $this->assertEquals($a11ycheck->haslanguage, $results->haslanguage);
                $this->assertEquals($a11ycheck->hastext, $results->hastext);
            }
        }
    }

}
