<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Universal catalogue settings.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */
$ADMIN->add('root', new admin_category('tool_mucatalog', new lang_string('pluginname', 'tool_mucatalog')));

$settings = new admin_settingpage(
    'tool_mucatalog_settings',
    new lang_string('settings', 'tool_mucatalog'),
    'moodle/site:config'
);
$ADMIN->add('tool_mucatalog', $settings);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'tool_mucatalog/addmenu',
        new lang_string('setting_addmenu', 'tool_mucatalog'),
        new lang_string('setting_addmenu_desc', 'tool_mucatalog'),
        1
    ));
}

$ADMIN->add('tool_mucatalog', new admin_externalpage(
    'tool_mucatalog_management_sections',
    new lang_string('management_sections', 'tool_mucatalog'),
    new url('/admin/tool/mucatalog/management/sections.php'),
    'tool/mucatalog:view'
));

$ADMIN->add('tool_mucatalog', new admin_externalpage(
    'tool_mucatalog_management_collections',
    new lang_string('management_collections', 'tool_mucatalog'),
    new url('/admin/tool/mucatalog/management/collections.php'),
    'tool/mucatalog:view'
));
