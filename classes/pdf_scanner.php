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
 * @package   local_accessibility_filescan
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accessibility_filescan;
use Exception;

/**
 * A class to orchestrate the scanning of a pdf for a11y
 */
class pdf_scanner {
    /**
     * Scan a pdf for a11y
     * @param string $file The filepath to the pdf
     * @return pdf_a11y_results
     * @throws Exception
     */
    public static function scan($file): pdf_a11y_results {
        // Initiate the new results object.
        $results = new pdf_a11y_results();
        $info = self::get_pdfinfo($file);

        foreach ($info as $line) {
            if (strpos($line, 'Pages:') === 0) {
                $results->pagecount = trim(explode(":", $line, 2)[1]);
            } else if (strpos($line, 'Tagged:') === 0) {
                $results->istagged = (trim(explode(":", $line, 2)[1]) === "yes") ? 1 : 0;
            }
        }
    
        $title = self::get_pdf_title($file, $info);
        $results->hastitle = (strlen($title) > 0) ? 1 : 0;
    
        $text = self::get_pdftext($file, $results->pagecount === 0 ? 1 : $results->pagecount);
        $results->hastext = intval($text && count($text) > 1);
    
        $lang = self::get_pdf_lang($file);
        $results->haslanguage = count($lang) > 1 ? 1 : 0;


        return $results;
    }

    /**
     * Extract the title from a pdf, checking both the classic Info dictionary
     * and XMP metadata (PDF 2.0 may only populate XMP, per spec).
     * @param string $file The filepath to the pdf
     * @param array $info The pdfinfo output lines
     * @return string
     */
    private static function get_pdf_title(string $file, array $info): string {
        // First, check the classic Info dictionary Title: line.
        foreach ($info as $line) {
            if (strpos($line, 'Title:') === 0) {
                $title = trim(explode(":", $line, 2)[1]);
                if (strlen($title) > 0) {
                    return $title;
                }
            }
        }
    
        // Fall back to XMP dc:title, which is what PDF 2.0 files use.
        $contents = file_get_contents($file);
        if (preg_match('/<dc:title>.*?<rdf:li[^>]*>(.*?)<\/rdf:li>.*?<\/dc:title>/s', $contents, $matches)) {
            return trim($matches[1]);
        }
    
        return '';
    }

    
    /**
     * Extract the language from a pdf
     * @param string $file The filepath to the pdf
     * @return array
     */
    private static function get_pdf_lang(string $file): array {
        $contents = file_get_contents($file);
        preg_match('/\/Lang\s*\((.*)\)/mU', $contents, $matches);
        return $matches;
    }

    /**
     * Extract text from a pdf
     * @param string $file The filepath to the pdf
     * @param int $pagecount How many pages are in the pdf
     * @return array
     * @throws Exception
     */
    private static function get_pdftext(string $file, int $pagecount): array {
        $cmd = self::get_pdftotext_command_for_file($file, $pagecount);
        $text = exec($cmd, $output, $exitcode);
        if ($exitcode <> 0) {
            throw new Exception("Error getting PDF text. " . $exitcode);
        }
        return $output;
    }

    /**
     * Extract info from a pdf
     * @param string $file The filepath to the pdf
     * @return array
     * @throws Exception
     */
    private static function get_pdfinfo(string $file): array {
        $cmd = self::get_pdfinfo_command_for_file($file);
        exec($cmd, $output, $exitcode);
        // If a non-standard exit code is returned, throw an error.
        if ($exitcode <> 0) {
            throw new Exception("Error getting PDF info. " . $exitcode);
        }
        return $output;
    }

    /**
     * Get the pdfinfo command
     * @param string $pdffile The filepath to the pdf
     * @return string
     */
    private static function get_pdfinfo_command_for_file(string $pdffile): string {
        $pdftotextexec = \escapeshellarg('pdfinfo');
        $pdffilearg = \escapeshellarg($pdffile);
        return "$pdftotextexec $pdffilearg";
    }

    /**
     * Get the pdftotext command
     * @param string $pdffile The filepath to the pdf
     * @param int $pdfpagecount How many pages are in the pdf
     * @return string
     */
    private static function get_pdftotext_command_for_file(string $pdffile, int $pdfpagecount): string {
        $pdftotextexec = \escapeshellarg('pdftotext');
        $pdffilearg = \escapeshellarg($pdffile);
        $lastpage = \escapeshellarg(min($pdfpagecount, 50));
        return "$pdftotextexec $pdffilearg -f 1 -l $lastpage -";
    }
}
