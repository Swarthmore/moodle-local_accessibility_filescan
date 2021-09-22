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
 *  General helper functions local_a11y_check
 *
 * @package   local_a11y_check
 * @copyright 2021 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_a11y_check;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * General helper functions
 */
class halp {

    /**
     * Get instructors for a course 
     * @param int $courseid
     * @return array of user objects
     */
    public static function get_instructors_for_course(int $courseid) {
        global $DB;
     
        // This is the shortname in the database that identifes the user as a teacher.
        $teacher_role_shortname = 'editingteacher';

        // Create the query.
        $sql = "SELECT
            c.id as course_id,
            u.id as user_id,
            u.email as user_email,
            u.firstname as user_first,
            u.lastname as user_last,
            u.lastname as user_name,
            r.name as user_role
        FROM {course} c
        JOIN {context} ctx ON c.id = ctx.instanceid
        JOIN {role_assignments} ra ON ra.contextid = ctx.id
        JOIN {user} u ON u.id = ra.userid
        JOIN {role} r ON r.id = ra.roleid
        WHERE c.id = $courseid AND r.shortname = $teacher_role_shortname;";

        // Run the query.
        $instructors = $DB->get_records_sql($sql);

        // Return the results.
        return $instructors;
    }

}