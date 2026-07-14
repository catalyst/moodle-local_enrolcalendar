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

namespace local_enrolcalendar;

use core\event\course_category_updated;
use core\event\course_deleted;
use core\event\course_updated;

/**
 * Event observer.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle course updated event.
     *
     * @param course_updated $event
     */
    public static function course_updated(course_updated $event): void {
        $courseid = $event->courseid;

        if (manager::is_course_in_scope($courseid)) {
            manager::update_event_for_course($courseid);
        } else {
            // Course may have been moved out of scope.
            manager::delete_event_for_course($courseid);
        }
    }

    /**
     * Handle course deleted event.
     *
     * @param course_deleted $event
     */
    public static function course_deleted(course_deleted $event): void {
        manager::delete_event_for_course($event->courseid);
    }

    /**
     * Handle course category updated event.
     *
     * @param course_category_updated $event
     */
    public static function course_category_updated(course_category_updated $event): void {
        manager::sync_all_events();
    }
}
