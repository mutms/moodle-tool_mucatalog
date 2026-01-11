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
// phpcs:disable moodle.Files.RequireLogin.Missing

/**
 * Universal catalog item details.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_mucatalog\local\catalogue;

/** @var moodle_page $CFG */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */
/** @var moodle_database $USER */

require('../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

if (!\tool_mulib\local\mulib::is_mucatalog_active()) {
    redirect(new url('/'));
}

if (!empty($CFG->forcelogin)) {
    require_login();
}
require_capability('tool/mucatalog:browse', $syscontext);

$item = $DB->get_record('tool_mucatalog_item', ['id' => $id], '*', MUST_EXIST);
$section = $DB->get_record('tool_mucatalog_section', ['id' => $item->sectionid], '*', MUST_EXIST);
$sectioncontext = context::instance_by_id($section->contextid);

if (\tool_mulib\local\mulib::is_mutenancy_active()) {
    $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
} else {
    $tenantid = null;
}

$currenturl = new url('/admin/tool/mucatalog/item.php', ['id' => $item->id]);
$canview = has_capability('tool/mucatalog:view', $sectioncontext);

if (!$canview && !catalogue::is_item_visible($item, $section, $USER->id, $tenantid)) {
    require_capability('tool/mucatalog:view', $sectioncontext);
}

$PAGE->set_context($syscontext);
$PAGE->set_url($currenturl);

$typeclass = \tool_mucatalog\local\item::get_type_classname($item->type);
if (!$typeclass || !$typeclass::is_available()) {
    throw new \core\exception\invalid_parameter_exception('Invalid item type');
}

$catalogue = get_string('catalogue_title', 'tool_mucatalog');
$itemname = format_string($item->name);

$PAGE->set_cacheable(true);
$PAGE->set_secondary_navigation(false);
$PAGE->set_heading($catalogue);
$PAGE->set_title($itemname . \moodle_page::TITLE_SEPARATOR . $catalogue);
$PAGE->add_body_class('limitedwidth');

$actions = new \tool_mulib\output\header_actions(get_string('actions'));

if ($canview) {
    $url = new url('/admin/tool/mucatalog/management/item.php', ['id' => $item->id]);
    $actions->get_dropdown()->add_item(get_string('management_item', 'tool_mucatalog'), $url, new \core\output\pix_icon('i/settings', ''));
}

if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

echo $OUTPUT->render(new \tool_mucatalog\output\item($item, $section));

echo $OUTPUT->footer();
