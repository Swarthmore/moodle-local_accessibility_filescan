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
 *  Courseinfo object for db.
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * Courseinfo object for db.
 */
class courseinfo {
    /**
     * Constructor function
     * @param int $id The course id
     * @param int $category The course category id
     * @param string $fullname The course fullname
     * @param string $shortname The course shortname
     * @param int $start The course start datetime
     * @param int $end The course end datetime
     * @param array $instructors The course instructors
     */
    public function __construct(int $id, int $category, string $fullname, string $shortname, int $start, int $end, array $instructors) {
        $this->id = $id;
        $this->category = $category;
        $this->fullname = $fullname;
        $this->shortname = $shortname;
        $this->start = $start;
        $this->end = $end;
        $this->instructors = $instructors;
    }
}