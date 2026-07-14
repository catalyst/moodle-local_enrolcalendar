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

use advanced_testcase;

/**
 * Unit tests for the enrolcalendar observer.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_enrolcalendar\observer
 */
final class observer_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_enrolcalendar');
    }

    /**
     * Helper to get course event count.
     * @param int $courseid
     * @return int event count
     */
    private function get_event_count(int $courseid): int {
        global $DB;
        return $DB->count_records('event', [
            'component' => manager::COMPONENT,
            'courseid' => $courseid,
            'eventtype' => 'course',
        ]);
    }

    /**
     * Test that updating course start date updates the event.
     */
    public function test_course_update_updates_event(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::create_event_for_course($course->id);
        $this->assertEquals(1, $this->get_event_count($course->id));

        // Update course start date via the proper API.
        $newstart = time() + (DAYSECS * 30);
        update_course((object) [
            'id' => $course->id,
            'startdate' => $newstart,
            'category' => $category->id,
        ]);

        $event = $DB->get_record('event', [
            'component' => manager::COMPONENT,
            'courseid' => $course->id,
            'eventtype' => 'course',
        ]);
        $this->assertEquals($newstart, $event->timestart);
    }

    /**
     * Test that moving a course out of scope deletes events.
     */
    public function test_course_moved_out_of_scope_deletes_events(): void {
        $inscope = $this->getDataGenerator()->create_category();
        $outscope = $this->getDataGenerator()->create_category();
        set_config('categoryid', $inscope->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $inscope->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::create_event_for_course($course->id);
        $this->assertEquals(1, $this->get_event_count($course->id));

        // Move course out of scope.
        update_course((object) [
            'id' => $course->id,
            'category' => $outscope->id,
        ]);

        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test that course update in scope creates event if missing.
     */
    public function test_course_update_in_scope_creates_event_if_missing(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        // No event exists.
        $this->assertEquals(0, $this->get_event_count($course->id));

        // Update course (triggers observer which calls update_event_for_course).
        update_course((object) [
            'id' => $course->id,
            'startdate' => time() + (DAYSECS * 2),
            'category' => $category->id,
        ]);

        $this->assertEquals(1, $this->get_event_count($course->id));
    }

    /**
     * Test event creation for course in subcategory.
     */
    public function test_course_in_subcategory(): void {
        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);
        set_config('categoryid', $parent->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $child->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::create_event_for_course($course->id);

        $this->assertEquals(1, $this->get_event_count($course->id));
    }

    /**
     * Test course outside scope does not get event.
     */
    public function test_course_outside_scope_no_event(): void {
        $inscope = $this->getDataGenerator()->create_category();
        $outscope = $this->getDataGenerator()->create_category();
        set_config('categoryid', $inscope->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $outscope->id,
            'startdate' => time() + DAYSECS,
        ]);

        // Trigger a course update - should not create event since out of scope.
        update_course((object) [
            'id' => $course->id,
            'startdate' => time() + (DAYSECS * 2),
            'category' => $outscope->id,
        ]);

        $this->assertEquals(0, $this->get_event_count($course->id));
    }
}
