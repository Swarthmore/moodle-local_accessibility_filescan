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
 * Scan local pdfs for a11y
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

/**
 * A class to orchestrate the scanning of a pdf for a11y
 */
class localscanner {
    /**
     * Scan a pdf for a11y
     * @param string $content The content of the pdf
     * @return \stdClass
     */
    public static function scan($content) {

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($content);
        $details = $pdf->getDetails();

        $results = self::initresults();

        if (array_key_exists('Title', $details)) {
            $results->hastitle = true;
        }

        $results->haslanguage = self::extractlanguage($content);

        $bookmarks = self::extractbookmarks($pdf);

        if (!empty($bookmarks)) {
            if (count($bookmarks) > 0) {
                $results->hasoutline = true;
            }
        }

        $text = self::extracttext($pdf);

        if (strlen($text) > 0) {
            $results->hastext = true;
        }

        return $results;
    }

    /**
     * Creates a new results object
     * @return /stdClass
     */
    private static function initresults() {
        $results = new \stdClass;
        $results->hastitle = false;
        $results->hasoutline = false;
        $results->hastext = false;
        $results->haslanguage = false;
        return $results;
    }

    /**
     * Extract the language from a pdf's metadata
     * @param string $content The pdf file contents
     * @return boolean
     */
    private static function extractlanguage($content) {
        $haslanguage = false;
        preg_match_all("/lang\(([a-z\-]+?)\)/mi", $content, $matches);
        foreach ($matches as $match) {
            if (!empty($match)) {
                $haslanguage = true;
                continue;
            }
        }
        return $haslanguage;
    }

    /**
     * Extract bookmarks from a pdf
     * @param \smalot\pdfparser\Document $pdf The pdf object
     * @return array
     */
    private static function extractbookmarks($pdf) {
        $bookmarks = [];
        foreach ($pdf->getObjects() as $obj) {
            $details = $obj->getHeader()->getDetails();
            if (isset($details['Title'])) {
                $bookmarks[] = $details['Title'];
            }
        }
        return $bookmarks;
    }

    /**
     * Extract text from a pdf
     * @param \smalot\pdfparser\Document $pdf The pdf object
     * @return string
     */
    private static function extracttext($pdf) {
        $pages = $pdf->getPages();
        $text = '';
        foreach ($pages as $page) {
            $pagetext = $page->getText();
            $text = $text . $pagetext;
        }
        return trim($text);
    }

}
