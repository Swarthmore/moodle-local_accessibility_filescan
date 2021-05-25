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

//defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

/**
 * A class to orchestrate the scanning of a pdf for a11y
 */
class scanner {
    /**
     * Scan a pdf for a11y
     * 
     * @param $content The content of the pdf
     * 
     * @return \stdClass $results The a11y results
     */
    public static function scan($content) {

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($content);
        $details = $pdf->getDetails();

        $results = self::_initResultsObject();
        
        // check for title
        if (array_key_exists('Title', $details)) {
            $results->hastitle = true;
        }

        // check for language
        $results->haslanguage = self::_extractLanguage($content);

        // check for bookmarks
        $bookmarks = self::_extractBookmarks($pdf);

        if (!empty($bookmarks)) {
            if (count($bookmarks) > 0) {
                $results->hasoutline = true;
            }
        }

        // check for page text
        $text = self::_extractText($pdf);

        var_dump($text);

        if (strlen($text) > 1) {
            $results->hastext = true;
        }

        return $results;
    }

    /**
     * Creates a new results object
     * 
     * @return /stdClass
     */
    private static function _initResultsObject() {
        $results = new \stdClass;
        $results->hastitle = false;
        $results->hasoutline = false;
        $results->hastext = false;
        $results->haslanguage = false;
        return $results;
    }

    /**
     * Extract the language from a pdf's metadata
     * 
     * @param $content - The pdf file contents 
     * 
     * @returns boolean
     */
    private static function _extractLanguage($content) {
        $haslanguage = false;
        // check for lang string
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
     * 
     * @param $pdf - The pdf object
     * 
     * @returns [] 
     */
    private static function _extractBookmarks($pdf) {
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
     * 
     * @param $pdf - The pdf object
     * 
     * @returns string
     */
    private static function _extractText($pdf) {
        $pages = $pdf->getPages();
        $text = "";
        foreach ($pages as $page) {
            $pagetext = $page->getText();
            $text = $text . $pagetext;
        }
        return trim($text);
    }

}
