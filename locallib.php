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
 * local library functions local_a11y_check
 *
 * @package   local_a11y_check
 * @copyright 2023 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Types of checks.
define('LOCAL_A11Y_CHECK_TYPE_UNDEFINED', 0);
define('LOCAL_A11Y_CHECK_TYPE_PDF', 1);

// Status types for scan checks.
define('LOCAL_A11Y_CHECK_STATUS_UNCHECKED', 0);
define('LOCAL_A11Y_CHECK_STATUS_PASS', 1);
define('LOCAL_A11Y_CHECK_STATUS_CHECK', 2);
define('LOCAL_A11Y_CHECK_STATUS_FAIL', 3);
define('LOCAL_A11Y_CHECK_STATUS_ERROR', 4);

// File is intentionally skipped, either from multiple errors, oversize, or some other issue.
define('LOCAL_A11Y_CHECK_STATUS_IGNORE', 5);
