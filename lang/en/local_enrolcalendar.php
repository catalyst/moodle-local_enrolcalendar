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
 * Language strings for local_enrolcalendar.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['disabled'] = 'Disabled';
$string['eventname'] = '{$a}';
$string['pluginname'] = 'Enrolment calendar events';
$string['privacy:metadata'] = 'This plugin does not store any personal user data. It creates course-level calendar events visible to enrolled users.';
$string['setting:categoryid'] = 'Category';
$string['setting:categoryid_desc'] = 'Courses in this category (and its subcategories) will have calendar events created for enrolled users. Changing this setting will trigger a full sync via an adhoc task.';
$string['setting:description'] = 'This plugin creates personal calendar events for users when they are enrolled in courses within a configured category. Events link to the course and reflect the course start date. Events are automatically updated upon category, course or enrolment update. An additional <a href="{$a}">scheduled task</a> runs daily to reconcile events.';
$string['setting:enabled'] = 'Enable';
$string['setting:enabled_desc'] = 'When disabled, all calendar events created by this plugin will be deleted. Changing this setting will trigger a full sync via an adhoc task.';
$string['synctask'] = 'Sync enrolment calendar events';
