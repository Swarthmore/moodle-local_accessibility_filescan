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

require_admin();

// Page setup.
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/a11y_check/views/report.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title('A11y Check - Scanned PDFs');
$PAGE->set_heading('A11y Check - Scanned PDFs');

// Get the report.
$report = \local_a11y_check\report::generate_report();

echo $OUTPUT->header();

echo '<table class="table">';
echo '<thead>';
echo '<tr>';
echo '<th scopr="col">Filename</th>';
echo '<th scope="col">Title</th>';
echo '<th scope="col">Language</th>';
echo '<th scope="col">Tagged</th>';
echo '<th scope="col">Pages</th>';
echo '</tr>';
echo '</thead>';

echo '<tbody>';
foreach ($report as $row) {

    $hastitlemark = $row->hastitle == 1 ? '✓' : '';
    $haslanguagemark = $row->haslanguage == 1 ? '✓' : '';
    $istaggedmark = $row->istagged == 1 ? '✓' : '';

    $rowclass = $row->hastitle == 1 && $row->haslanguage == 1 && $row->istagged == 1 ? 'table-success' : '';
    echo "<tr class=\"$rowclass\">";
    echo "<td>$row->filename</td>";
    echo '<td class="text-success">' . $hastitlemark . '</td>';
    echo '<td class="text-success">' . $haslanguagemark . '</td>';
    echo '<td class="text-success">' . $istaggedmark . '</td>';
    echo '<td>' . $row->pagecount . '</td>';
    echo '</tr>';

}

echo '</tbody>';
echo "</table>";

echo '<script crossorigin src="https://unpkg.com/react@17/umd/react.production.min.js"></script>';

echo $OUTPUT->footer();