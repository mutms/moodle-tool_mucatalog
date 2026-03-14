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
 * Move item to different section.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$item = $DB->get_record('tool_mucatalog_item', ['id' => $id], '*', MUST_EXIST);
$section = $DB->get_record('tool_mucatalog_section', ['id' => $item->sectionid], '*', MUST_EXIST);
$context = context::instance_by_id($section->contextid);

require_login();
require_capability('tool/mucatalog:manage', $context);

$currenturl = new url('/admin/tool/mucatalog/management/item_move.php', ['id' => $item->id]);
$returnurl = new url('/admin/tool/mucatalog/management/item.php', ['id' => $item->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$itemclass = \tool_mucatalog\local\item::get_type_classname($item->type);
if (!$itemclass) {
    redirect($returnurl);
}

$form = new \tool_mucatalog\local\form\item_move(null, ['section' => $section, 'context' => $context, 'item' => $item]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $item = $itemclass::move($data->id, $data->sectionid);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
