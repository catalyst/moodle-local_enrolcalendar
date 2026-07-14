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

use calendar_event;
use core_course_category;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Manager class for enrolment calendar events.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string Component name used to tag calendar events */
    public const COMPONENT = 'local_enrolcalendar';

    /**
     * Check if the plugin is enabled.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config('local_enrolcalendar', 'enabled');
    }

    /**
     * Get the configured category ID.
     *
     * @return int The category ID, or 0 if not configured.
     */
    public static function get_configured_category_id(): int {
        return (int) get_config('local_enrolcalendar', 'categoryid');
    }

    /**
     * Get all category IDs in scope (the configured category and all its descendants).
     *
     * @return array Array of category IDs, empty if not configured.
     */
    public static function get_category_ids_in_scope(): array {
        if (!self::is_enabled()) {
            return [];
        }

        $categoryid = self::get_configured_category_id();
        if (empty($categoryid)) {
            return [];
        }

        try {
            $category = core_course_category::get($categoryid);
        } catch (\moodle_exception $e) {
            return [];
        }

        $ids = [$categoryid];
        $children = $category->get_all_children_ids();
        return array_merge($ids, $children);
    }

    /**
     * Check if a course is within the configured category scope.
     *
     * @param int $courseid
     * @return bool
     */
    public static function is_course_in_scope(int $courseid): bool {
        global $DB;

        $categoryids = self::get_category_ids_in_scope();
        if (empty($categoryids)) {
            return false;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'id, category');
        if (!$course) {
            return false;
        }

        return in_array((int) $course->category, $categoryids);
    }

    /**
     * Create a course-level calendar event for a course.
     *
     * Does nothing if an event already exists for this course, or if the course has no start date.
     *
     * @param int $courseid
     */
    public static function create_event_for_course(int $courseid): void {
        global $DB;

        if (!self::is_enabled()) {
            return;
        }

        // Check if event already exists.
        $existing = $DB->get_record('event', [
            'component' => self::COMPONENT,
            'courseid' => $courseid,
            'eventtype' => 'course',
        ]);

        if ($existing) {
            return;
        }

        $course = get_course($courseid);
        if (empty($course->startdate)) {
            return;
        }

        $event = new \stdClass();
        $event->name = get_string('eventname', 'local_enrolcalendar', $course->shortname);
        $event->eventtype = 'course';
        $event->courseid = $courseid;
        $event->timestart = $course->startdate;
        $event->timeduration = 0;
        $event->component = self::COMPONENT;
        $event->description = get_string('eventname', 'local_enrolcalendar', $course->shortname);
        $event->format = FORMAT_PLAIN;

        calendar_event::create($event, false);
    }

    /**
     * Delete the calendar event for a course.
     *
     * @param int $courseid
     */
    public static function delete_event_for_course(int $courseid): void {
        global $DB;

        $events = $DB->get_records('event', [
            'component' => self::COMPONENT,
            'courseid' => $courseid,
            'eventtype' => 'course',
        ]);

        foreach ($events as $event) {
            $calendarevent = calendar_event::load($event);
            $calendarevent->delete();
        }
    }

    /**
     * Update the calendar event for a course (e.g. when start date changes).
     *
     * @param int $courseid
     */
    public static function update_event_for_course(int $courseid): void {
        global $DB;

        $course = get_course($courseid);

        if (empty($course->startdate)) {
            // No start date, delete the event for this course.
            self::delete_event_for_course($courseid);
            return;
        }

        $events = $DB->get_records('event', [
            'component' => self::COMPONENT,
            'courseid' => $courseid,
            'eventtype' => 'course',
        ]);

        if (empty($events)) {
            // Event doesn't exist yet, create it.
            self::create_event_for_course($courseid);
            return;
        }

        foreach ($events as $event) {
            $calendarevent = calendar_event::load($event);
            $calendarevent->update((object) [
                'name' => get_string('eventname', 'local_enrolcalendar', $course->shortname),
                'timestart' => $course->startdate,
            ], false);
        }
    }

    /**
     * Full sync of all events. Creates missing events, deletes stale ones, updates existing ones.
     *
     * This is designed to be performant at scale by using set-based operations where possible.
     */
    public static function sync_all_events(): void {
        global $DB;

        $categoryids = self::get_category_ids_in_scope();

        // Step 1: Delete events for courses no longer in scope.
        $allevents = $DB->get_records('event', [
            'component' => self::COMPONENT,
            'eventtype' => 'course',
        ], '', 'id, courseid');

        if (!empty($allevents)) {
            $courseids = array_unique(array_column($allevents, 'courseid'));

            if (!empty($courseids)) {
                if (empty($categoryids)) {
                    // Nothing is in scope, delete all.
                    $outofscope = $courseids;
                } else {
                    list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
                    list($catsql, $catparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat', false);
                    $sql = "SELECT id FROM {course} WHERE id $insql AND category $catsql";
                    $outofscope = $DB->get_fieldset_sql($sql, array_merge($params, $catparams));
                }

                foreach ($outofscope as $courseid) {
                    self::delete_event_for_course((int) $courseid);
                }
            }
        }

        if (empty($categoryids)) {
            return;
        }

        // Step 2: For courses in scope, ensure events exist and are up to date.
        list($catsql, $catparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
        $courses = $DB->get_records_select(
            'course',
            "category $catsql AND startdate > 0",
            $catparams,
            '',
            'id, shortname, startdate'
        );

        foreach ($courses as $course) {
            $existing = $DB->get_record('event', [
                'component' => self::COMPONENT,
                'courseid' => $course->id,
                'eventtype' => 'course',
            ]);

            if (!$existing) {
                self::create_event_for_course((int) $course->id);
            } else if ((int) $existing->timestart !== (int) $course->startdate) {
                $calendarevent = calendar_event::load($existing);
                $calendarevent->update((object) [
                    'name' => get_string('eventname', 'local_enrolcalendar', $course->shortname),
                    'timestart' => $course->startdate,
                ], false);
            }
        }
    }
}
