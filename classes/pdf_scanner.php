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

require_once(dirname(__FILE__) . "/../vendor/autoload.php");
require_once(dirname(__FILE__) . "/pdf_a11y_results.php");

/**
 * A class to orchestrate the scanning of a pdf for a11y
 */
class pdf_scanner {
    /**
     * Scan a pdf for a11y
     * @param string $file The filepath to the pdf
     * @return \pdf_a11y_results
     */
    public static function scan($file) {
        // Initiate the new results object.
        $results = new \local_a11y_check\pdf_a11y_results();
        $pagecount = 0;
        $info = self::get_pdfinfo($file);

        // Iterate through the output lines and assign a11y results.
        foreach ($info as $line) {
            if (substr($line, 0, strlen("Title:")) === "Title:") {
                $results->hastitle = (strlen(trim(explode(":", $line, 2)[1])) > 0) ? 1 : 0;
            } else if (substr($line, 0, strlen("Pages:")) === "Pages:") {
                $pagecount = trim(explode(":", $line, 2)[1]);
            }
        }

        // Get the hastext status.
        $text = self::get_pdftext($file, $pagecount);
        $results->hastext = intval($text && count($text) > 1);

        // Get the haslanguage status.
        $lang = self::get_pdf_lang($file);
        $results->haslanguage = empty($lang) ? 0 : 1;

        // Get the outline.
        $outline = self::extract_outline($file);
        $results->hasoutline = empty($outline) ? 0 : 1;

        return $results;
    }

    private static function extract_outline(string $file) {
        $contents = file_get_contents($file);
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($contents);
        $outline = array();
        foreach ($pdf->getObjects() as $obj) {
            $details = $obj->getHeader()->getDetails();
            if (isset($details["Title"])) {
                if (isset($details["A"])) {
                    $outline[] = $details;
                } else if (isset($details["Dest"])) {
                    $outline[] = $details;
                } else if (isset($details["First"]) && isset($details["Last"])) {
                    $outline[] = $details;
                } else if (isset($details["Next"])) {
                    $outline[] = $details;
                }
            }
        }
        return $outline;
    }

    private function get_pdf_lang(string $file) {
        $contents = file_get_contents($file);
        preg_match('/\/Lang\((.*)\)/mU', $contents, $matches);
        return count($matches) > 1 && $matches[1] ? $pdfcheck["haslanguage"] = $matches[1] : "";
    }

    private function get_pdftext(string $file, int $pagecount) {
        $cmd = self::get_pdftotext_command_for_file($file, $pagecount);
        $text = exec($cmd, $output, $exitcode);
        if ($exitcode <> 0) {
            throw new \Exception("Error getting PDF text. " . $exitcode);
        }
        return $output;
    }

    private function get_pdfinfo(string $file) {
        $cmd = self::get_pdfinfo_command_for_file($file);
        exec($cmd, $output, $exitcode);
        // If a non-standard exit code is returned, throw an error.
        if ($exitcode <> 0) {
            throw new \Exception("Error getting PDF info. " . $exitcode);
        }
        return $output;
    }

    private function get_pdfinfo_command_for_file(string $pdffile) {
        $pdftotextexec = \escapeshellarg('pdfinfo');
        $pdffilearg = \escapeshellarg($pdffile);
        return "$pdftotextexec $pdffilearg";
    }

    private function get_pdftotext_command_for_file(string $pdffile, int $pdfpagecount) {
        $pdftotextexec = \escapeshellarg('pdftotext');
        $pdffilearg = \escapeshellarg($pdffile);
        $lastpage = \escapeshellarg(min($pdfpagecount, 50));
        return "$pdftotextexec $pdffilearg -f 1 -l $lastpage -";
    }
}
