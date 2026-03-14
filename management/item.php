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
// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch

/**
 * Catalogue item management page.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucatalog\local\management;
use core\url;
use tool_mulib\output\ajax_form\button;
use tool_mulib\output\header_actions;
use tool_mucatalog\local\item;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$item = $DB->get_record('tool_mucatalog_item', ['id' => $id], '*', MUST_EXIST);
$section = $DB->get_record('tool_mucatalog_section', ['id' => $item->sectionid], '*', MUST_EXIST);
$context = context::instance_by_id($section->contextid);

require_login();
require_capability('tool/mucatalog:view', $context);

$currenturl = new url('/admin/tool/mucatalog/management/item.php', ['id' => $item->id]);

management::setup_item_page($currenturl, $context, $item, $section);

/** @var \tool_mucatalog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_mucatalog', 'management');

$actions = new header_actions(get_string('item_actions', 'tool_mucatalog'));

$buttons = [];
if (has_capability('tool/mucatalog:manage', $context)) {
    $url = new url('/admin/tool/mucatalog/item.php', ['id' => $item->id]);
    $button = $OUTPUT->single_button($url, get_string('item_preview', 'tool_mucatalog'), 'get');
    $actions->add_button($button);

    $url = new url('/admin/tool/mucatalog/management/item_update.php', ['id' => $item->id]);
    $button = new button($url, get_string('item_update', 'tool_mucatalog'));
    $actions->add_button($button);

    $url = new url('/admin/tool/mucatalog/management/item_move.php', ['id' => $item->id]);
    $link = new \tool_mulib\output\ajax_form\link($url, get_string('item_move', 'tool_mucatalog'), 'i/move_2d');
    $link->set_form_size('sm');
    $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
    $actions->get_dropdown()->add_ajax_form($link);

    if (item::is_delete_possible($section)) {
        $url = new url('/admin/tool/mucatalog/management/item_delete.php', ['id' => $item->id]);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('item_delete', 'tool_mucatalog'), 'i/delete');
        $link->add_class('text-danger');
        $link->set_form_size('sm');
        $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
        $actions->get_dropdown()->add_ajax_form($link);
    }
}

echo $OUTPUT->header();

echo $managementoutput->render_item($item, $section);

if ($actions->has_items()) {
    $buttons = $OUTPUT->render($actions);
    echo $OUTPUT->box($buttons, 'buttons');
}

echo $OUTPUT->footer();
