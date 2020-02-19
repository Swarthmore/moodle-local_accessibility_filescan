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
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PDF evaluation unit test.
 *
 * @package   local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_a11y_assert_pdf_evaluation_testcase extends advanced_testcase {
    public function test_pdf_evaluation() {
        $this->resetAfterTest(true);

        // Get the results.
        $passpdf  = json_decode(file_get_contents(dirname(__FILE__) . '/fixtures/record-pass.json'));
        $checkpdf = json_decode(file_get_contents(dirname(__FILE__) . '/fixtures/record-check.json'));
        $failpdf  = json_decode(file_get_contents(dirname(__FILE__) . '/fixtures/record-fail.json'));

        // Check evaluation.
        $this->assertEquals(LOCAL_A11Y_CHECK_STATUS_PASS, \local_a11y_check\pdf::evaluate_item_status($passpdf));
        $this->assertEquals(LOCAL_A11Y_CHECK_STATUS_CHECK, \local_a11y_check\pdf::evaluate_item_status($checkpdf));
        $this->assertEquals(LOCAL_A11Y_CHECK_STATUS_FAIL, \local_a11y_check\pdf::evaluate_item_status($failpdf));
    }
}