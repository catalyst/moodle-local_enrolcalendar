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

use local_enrolcalendar\manager;

/**
 * Scheduled task to sync enrolment calendar events.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_events extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('synctask', 'local_enrolcalendar');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        manager::sync_all_events();
    }

    /**
     * Whether the component is enabled.
     *
     * @return bool
     */
    public function is_component_enabled(): bool {
        // Overwriting this method stops this task from running when disabled.
        return manager::is_enabled();
    }
}
