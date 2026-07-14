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
 * Ad-hoc task to sync enrolment calendar events.
 *
 * Queued when plugin settings change (e.g. enabled/disabled, category changed).
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_events_adhoc extends \core\task\adhoc_task {

    /**
     * Execute the task.
     */
    public function execute(): void {
        // Note, we purposely run here even if the plugin is disabled.
        // this is because the sync needs to run post-enable or post-disable,
        // to align the new state.

        manager::sync_all_events();
    }
}
