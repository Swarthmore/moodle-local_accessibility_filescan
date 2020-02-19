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
 * File discovery unit test.
 *
 * @package   local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_a11y_check_testcase extends advanced_testcase {
    /** @var \local_a11y_check\pdf The pdf helper object */
    public $pdfhelper;

    /** @var \local\a11y_check\task\find_pdf_files The task object */
    public $task;

    /** @var \stdClass the test course object */
    public $course;

    /** @var array count of generated pdfs (used to ensure uniqueness) */
    public $filesaddedcount;

    public function setUp() {
        $this->resetAfterTest();
        $this->pdfhelper   = new \local_a11y_check\pdf();
        $this->task        = new \local_a11y_check\task\find_pdf_files();
        $this->course      = $this->getDataGenerator()->create_course(array('shortname' => 'testcourse'));
        $this->page        = $this->getDataGenerator()->create_module('page', array('course' => $this->course->id));
        $this->filestorage = \get_file_storage();

        set_config('maxsize', 10, 'local_a11y_check');
    }

    public function test_large_file() {
        global $DB;

        $this->add_pdfs(1, 1000);

        $this->assert_unscanned_files_count(1);
        $this->assert_custom_record_count(0);

        $this->task->execute();

        $this->assert_unscanned_files_count(0);
        $this->assert_custom_record_count(1);

        $records = $DB->get_records_sql("SELECT *
            FROM {local_a11y_check} ac
            JOIN {local_a11y_check_type_pdf} actp ON ac.id=actp.scanid");
        $record  = $records[array_keys($records)[0]];

        $this->assertEquals(LOCAL_A11Y_CHECK_STATUS_IGNORE, $record->status);
    }

    public function test_find_files() {
        $this->add_garbage_files();

        $this->assert_unscanned_files_count(0);
        $this->assert_custom_record_count(0);

        $this->add_pdfs(5);

        $this->assert_unscanned_files_count(5);
        $this->assert_custom_record_count(0);

        $this->task->execute();

        $this->assert_unscanned_files_count(0);
        $this->assert_custom_record_count(5);

        $this->add_pdfs(7);

        $this->assert_unscanned_files_count(7);
        $this->assert_custom_record_count(5);

        $this->task->execute();

        $this->assert_unscanned_files_count(0);
        $this->assert_custom_record_count(12);
    }

    /**
     * Create PDFs which should be excluded from the scan.
     */
    protected function add_garbage_files() {
        $garbage = array(
            (object) array(
                'contextid' => context_system::instance()->id,
            ),
            (object) array(
                'contextid' => context_user::instance(1)->id,
            ),
            (object) array(
                'contextid' => context_coursecat::instance(1)->id,
            ),
            (object) array(
                'contextid' => context_course::instance($this->course->id)->id,
            ),
            (object) array(
                'mimetype'  => 'application/json',
            ),
            (object) array(
                'component'  => 'assignfeedback_editpdf',
            ),
            (object) array(
                'filearea'  => 'stamps',
            ),
        );

        foreach ($garbage as $i => $record) {
            $uniquetext = (string) time() . (string) $this->filesaddedcount;
            $record->contextid = $record->contextid ?? context_module::instance($this->page->cmid)->id;
            $record->component = $record->component ?? 'local_a11y_check';
            $record->filearea  = $record->filearea ?? 'local_a11y_check_test_files';
            $record->itemid    = $record->itemid ?? $i;
            $record->filepath  = $record->filepath ?? '/';
            $record->filename  = $record->filename ?? 'local_a11y_check_test_file_' . $uniquetext . '.pdf';
            $record->mimetype  = $record->mimetype ?? 'application/pdf';
            $this->filestorage->create_file_from_string($record, $uniquetext);
            $this->filesaddedcount++;
        }

        $record = (object) array(
            'contextid' => context_module::instance($this->page->cmid)->id,
            'component' => 'local_a11y_check',
            'filearea'  => 'local_a11y_check_test_files',
            'itemid'    => $i,
            'filepath'  => '/',
            'filename'  => 'local_a11y_check_test_file_empty.pdf',
            'mimetype'  => 'application/pdf',
        );
        $this->filestorage->create_file_from_string($record, '');
        $this->filesaddedcount++;
    }

    /**
     * Create the requested number of PDFs.
     * These should all be found by the plugin.
     *
     * @param int $count The number of PDFs to create.
     * @param int $size Force filesize (in bytes).
     */
    protected function add_pdfs($count, $size = null) {
        for ($i = 0; $i < $count; $i++) {
            $uniquetext = (string) time() . (string) $this->filesaddedcount . (string) $i;
            $record = (object) array(
                'contextid' => context_module::instance($this->page->cmid)->id,
                'component' => 'local_a11y_check',
                'filearea'  => 'local_a11y_check_test_files',
                'itemid'    => $i,
                'filepath'  => '/',
                'filename'  => 'local_a11y_check_test_file_' . $uniquetext . '.pdf',
                'mimetype'  => 'application/pdf',
            );

            if (is_numeric($size)) {
                $record->filesize = $size;
            }

            $this->filestorage->create_file_from_string($record, $uniquetext);
            $this->filesaddedcount++;
        }
    }

    /**
     * Wrapper for assertEquals against the local_a11y_check table.
     *
     * @param int $count the expected number of records.
     *
     * @return boolean
     */
    protected function assert_custom_record_count($count) {
        global $DB;

        $customrecords = $DB->get_records('local_a11y_check');
        return $this->assertEquals($count, count($customrecords));
    }

    /**
     * Wrapper for assertEquals against get_unscanned_pdf_files.
     *
     * @param int $count the expected number of records.
     *
     * @return boolean
     */
    protected function assert_unscanned_files_count($count) {
        $queryresults = $this->pdfhelper->get_unscanned_pdf_files();
        return $this->assertEquals($count, count($queryresults));
    }
}