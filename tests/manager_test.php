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
 * Unit tests for the enrolcalendar manager.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_enrolcalendar\manager
 */
class manager_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_enrolcalendar');
    }

    /**
     * Helper to get course event count.
     *
     * @param int $courseid
     * @return int
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
     * Helper to get event record for a course.
     *
     * @param int $courseid
     * @return \stdClass|false
     */
    private function get_event_record(int $courseid) {
        global $DB;
        return $DB->get_record('event', [
            'component' => manager::COMPONENT,
            'courseid' => $courseid,
            'eventtype' => 'course',
        ]);
    }

    /**
     * Test event creation for a course in scope.
     */
    public function test_create_event_for_course_in_scope(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
            'shortname' => 'TEST101',
        ]);

        manager::create_event_for_course($course->id);

        $this->assertEquals(1, $this->get_event_count($course->id));
        $event = $this->get_event_record($course->id);
        $this->assertEquals($course->startdate, $event->timestart);
        $this->assertEquals('TEST101', $event->name);
    }

    /**
     * Test no event created when course has no start date.
     */
    public function test_no_event_when_no_start_date(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => 0,
        ]);

        manager::create_event_for_course($course->id);

        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test duplicate prevention - only one event per course.
     */
    public function test_duplicate_prevention(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::create_event_for_course($course->id);
        manager::create_event_for_course($course->id);

        $this->assertEquals(1, $this->get_event_count($course->id));
    }

    /**
     * Test event deletion for a course.
     */
    public function test_delete_event_for_course(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::create_event_for_course($course->id);
        $this->assertEquals(1, $this->get_event_count($course->id));

        manager::delete_event_for_course($course->id);
        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test event update when course start date changes.
     */
    public function test_update_event_for_course(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
            'shortname' => 'UPDATED',
        ]);

        manager::create_event_for_course($course->id);

        // Update course start date.
        $newstart = time() + (DAYSECS * 7);
        $DB->set_field('course', 'startdate', $newstart, ['id' => $course->id]);
        purge_all_caches();

        manager::update_event_for_course($course->id);

        $event = $this->get_event_record($course->id);
        $this->assertEquals($newstart, $event->timestart);
    }

    /**
     * Test update creates event if it doesn't exist yet.
     */
    public function test_update_event_creates_if_missing(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        // No event exists yet.
        $this->assertEquals(0, $this->get_event_count($course->id));

        manager::update_event_for_course($course->id);

        $this->assertEquals(1, $this->get_event_count($course->id));
    }

    /**
     * Test events deleted when course start date set to 0.
     */
    public function test_update_removes_when_no_start_date(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::create_event_for_course($course->id);

        $DB->set_field('course', 'startdate', 0, ['id' => $course->id]);
        purge_all_caches();

        manager::update_event_for_course($course->id);

        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test is_course_in_scope with subcategories.
     */
    public function test_is_course_in_scope_with_subcategories(): void {
        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);
        $grandchild = $this->getDataGenerator()->create_category(['parent' => $child->id]);
        $unrelated = $this->getDataGenerator()->create_category();

        set_config('categoryid', $parent->id, 'local_enrolcalendar');

        $course1 = $this->getDataGenerator()->create_course(['category' => $parent->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $child->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $grandchild->id]);
        $course4 = $this->getDataGenerator()->create_course(['category' => $unrelated->id]);

        $this->assertTrue(manager::is_course_in_scope($course1->id));
        $this->assertTrue(manager::is_course_in_scope($course2->id));
        $this->assertTrue(manager::is_course_in_scope($course3->id));
        $this->assertFalse(manager::is_course_in_scope($course4->id));
    }

    /**
     * Test is_course_in_scope returns false when no category configured.
     */
    public function test_is_course_in_scope_no_config(): void {
        set_config('categoryid', 0, 'local_enrolcalendar');

        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);

        $this->assertFalse(manager::is_course_in_scope($course->id));
    }

    /**
     * Test sync creates missing events for courses in scope.
     */
    public function test_sync_creates_missing_events(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        // No events exist yet.
        $this->assertEquals(0, $this->get_event_count($course->id));

        manager::sync_all_events();

        $this->assertEquals(1, $this->get_event_count($course->id));
    }

    /**
     * Test sync deletes events for courses no longer in scope.
     */
    public function test_sync_deletes_out_of_scope_events(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        $other = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $other->id,
            'startdate' => time() + DAYSECS,
        ]);

        // Manually create an event for a course not in scope.
        $event = new \stdClass();
        $event->name = 'test';
        $event->eventtype = 'course';
        $event->courseid = $course->id;
        $event->timestart = $course->startdate;
        $event->timeduration = 0;
        $event->component = manager::COMPONENT;
        $event->description = 'test';
        $event->format = FORMAT_PLAIN;
        \calendar_event::create($event, false);

        $this->assertEquals(1, $this->get_event_count($course->id));

        manager::sync_all_events();

        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test sync updates event timestart when course start date changed.
     */
    public function test_sync_updates_timestart(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $startdate = time() + DAYSECS;
        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => $startdate,
        ]);

        manager::create_event_for_course($course->id);

        // Change course start date.
        $newstart = time() + (DAYSECS * 14);
        $DB->set_field('course', 'startdate', $newstart, ['id' => $course->id]);
        purge_all_caches();

        manager::sync_all_events();

        $event = $this->get_event_record($course->id);
        $this->assertEquals($newstart, $event->timestart);
    }

    /**
     * Test category tree edge case - course moved out of scope.
     */
    public function test_sync_handles_category_tree_change(): void {
        $root = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $root->id]);
        $unrelated = $this->getDataGenerator()->create_category();

        set_config('categoryid', $root->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $child->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::sync_all_events();
        $this->assertEquals(1, $this->get_event_count($course->id));

        // Move course to unrelated category.
        move_courses([$course->id], $unrelated->id);

        manager::sync_all_events();
        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test that disabling the plugin (category = 0) removes all events on sync.
     */
    public function test_sync_removes_all_when_category_cleared(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::sync_all_events();
        $this->assertEquals(1, $this->get_event_count($course->id));

        // Clear category.
        set_config('categoryid', 0, 'local_enrolcalendar');

        manager::sync_all_events();
        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test that setting enabled=0 removes all events on sync.
     */
    public function test_sync_removes_all_when_enabled_flag_off(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::sync_all_events();
        $this->assertEquals(1, $this->get_event_count($course->id));

        // Disable via enabled flag.
        set_config('enabled', 0, 'local_enrolcalendar');

        manager::sync_all_events();
        $this->assertEquals(0, $this->get_event_count($course->id));
    }

    /**
     * Test that create_event_for_course does nothing when plugin is disabled.
     */
    public function test_no_event_when_disabled(): void {
        $category = $this->getDataGenerator()->create_category();
        set_config('enabled', 0, 'local_enrolcalendar');
        set_config('categoryid', $category->id, 'local_enrolcalendar');

        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
            'startdate' => time() + DAYSECS,
        ]);

        manager::create_event_for_course($course->id);

        $this->assertEquals(0, $this->get_event_count($course->id));
    }
}
