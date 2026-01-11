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
// phpcs:disable moodle.Files.LineLength.TooLong

/**
 * Universal catalogue core APIs.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

/**
 * Hook called before a course category is deleted.
 *
 * @param \stdClass $category The category record.
 */
function tool_mucatalog_pre_course_category_delete(\stdClass $category) {
    \tool_mucatalog\local\section::pre_course_category_delete($category->id);
    \tool_mucatalog\local\collection::pre_course_category_delete($category->id);
}

/**
 * This function extends the category navigation with catalogue sections.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $coursecategorycontext The context of the course category
 */
function tool_mucatalog_extend_navigation_category_settings($navigation, $coursecategorycontext): void {
    if (!has_capability('tool/mucatalog:view', $coursecategorycontext)) {
        return;
    }

    // NOTE: catnav is added to unbreak breadcrumbs on management pages.
    $settingsnode = navigation_node::create(
        get_string('management_sections', 'tool_mucatalog'),
        new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $coursecategorycontext->id, 'catnav' => 1]),
        navigation_node::TYPE_CUSTOM,
        null,
        'tool_mucatalog_management'
    );
    $settingsnode->set_force_into_more_menu(true);
    $navigation->add_node($settingsnode);
}

/**
 * Program file serving support.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return void
 */
function tool_mucatalog_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea === 'item_image') {
        if ($CFG->forcelogin) {
            require_login();
        }

        $itemid = (int)array_shift($args);
        $filepath = implode('/', $args);

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $itemid]);
        if (!$item) {
            send_file_not_found();
        }

        $typeclass = \tool_mucatalog\local\item::get_type_classname($item->type);
        if (!$typeclass) {
            send_file_not_found();
        }

        // Do not waste time with access control.

        if ($filepath === 'geopattern.svg') {
            $typeclass::send_geopattern($item);
        }

        $typeclass::send_image($item);
    }

    if ($filearea === 'item_description') {
        if ($CFG->forcelogin) {
            require_login();
        }

        $itemid = (int)array_shift($args);
        $filename = array_pop($args);
        $filepath = implode('/', $args);

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $itemid]);
        if (!$item) {
            send_file_not_found();
        }

        $typeclass = \tool_mucatalog\local\item::get_type_classname($item->type);
        if (!$typeclass) {
            send_file_not_found();
        }

        $typeclass::send_description_file($item, $filepath, $filename);
    }

    send_file_not_found();
}
