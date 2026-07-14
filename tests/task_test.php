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

namespace local_enrolcalendar\task;

use advanced_testcase;

/**
 * Unit tests for the enrolcalendar tasks.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_enrolcalendar\task\sync_events
 * @covers      \local_enrolcalendar\task\sync_events_adhoc
 */
class task_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test scheduled task is_component_enabled follows the enabled setting.
     */
    public function test_scheduled_task_enabled_when_plugin_enabled(): void {
        set_config('enabled', 1, 'local_enrolcalendar');
        $task = new sync_events();
        $this->assertTrue($task->is_component_enabled());
    }

    /**
     * Test scheduled task is_component_enabled returns false when disabled.
     */
    public function test_scheduled_task_disabled_when_plugin_disabled(): void {
        set_config('enabled', 0, 'local_enrolcalendar');
        $task = new sync_events();
        $this->assertFalse($task->is_component_enabled());
    }
}
