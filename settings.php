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
 * Plugin settings.
 *
 * @package     local_enrolcalendar
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_enrolcalendar', get_string('pluginname', 'local_enrolcalendar'));
    $ADMIN->add('localplugins', $settings);

    $taskurl = new moodle_url('/admin/tool/task/scheduledtasks.php', [],
        'local_enrolcalendar-task-sync_events');
    $settings->add(new admin_setting_heading(
        'local_enrolcalendar/description',
        '',
        get_string('setting:description', 'local_enrolcalendar', $taskurl->out())
    ));

    $enabledsetting = new admin_setting_configcheckbox(
        'local_enrolcalendar/enabled',
        get_string('setting:enabled', 'local_enrolcalendar'),
        get_string('setting:enabled_desc', 'local_enrolcalendar'),
        0
    );
    $enabledsetting->set_updatedcallback(function() {
        \core\task\manager::queue_adhoc_task(new \local_enrolcalendar\task\sync_events_adhoc(), true);
    });
    $settings->add($enabledsetting);

    $categorysetting = new admin_settings_coursecat_select(
        'local_enrolcalendar/categoryid',
        get_string('setting:categoryid', 'local_enrolcalendar'),
        get_string('setting:categoryid_desc', 'local_enrolcalendar'),
        0
    );
    $categorysetting->set_updatedcallback(function() {
        \core\task\manager::queue_adhoc_task(new \local_enrolcalendar\task\sync_events_adhoc(), true);
    });
    $settings->add($categorysetting);
}
