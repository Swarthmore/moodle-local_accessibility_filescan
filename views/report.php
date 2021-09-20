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
 * This page handles a general overview of PDF accessibility issues.
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../classes/report.php');

// Restrict this page to administrators only.
require_admin();

// Page setup.
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/a11y_check/views/react-report.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('A11y Check - Scanned PDFs');
$PAGE->set_heading('A11y Check - Scanned PDFs');

echo $OUTPUT->header();

// Get the report.
$report = \local_a11y_check\report::generate_report();
$json = json_encode($report);

// Add the DOM element for the React app to attach to.
echo '<div id="a11y_check__Root"></div>';

// Add $json as a global variable so it can be accessed by the React app.
echo '<script>';
echo "const data = $json";
echo '</script>';

// Add the React app.
echo '<script src="js/report.js"></script>';

echo $OUTPUT->footer();
