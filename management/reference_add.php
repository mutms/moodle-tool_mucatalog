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
 * Add course, program or certification to a catalogue section.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_mucatalog\local\util;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require('../../../../config.php');

$type = required_param('type', PARAM_ALPHANUM);
$referenceid = required_param('referenceid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();

$typeclass = \tool_mucatalog\local\item::get_type_classname($type);
if (!$typeclass || !$typeclass::is_available()) {
    throw new \core\exception\invalid_parameter_exception('Invalid item type');
}
$context = $typeclass::get_reference_context($referenceid);
if (!$context) {
    throw new \core\exception\invalid_parameter_exception('Invalid item reference');
}
require_capability($typeclass::get_add_capability(), $context);
if (!$typeclass::is_reference_add_possible($referenceid)) {
    throw new \core\exception\invalid_parameter_exception('Item reference cannot be added to catalogue');
}

$currenturl = new url('/admin/tool/mucatalog/management/reference_add.php', ['type' => $type, 'referenceid' => $referenceid]);
if ($returnurl !== '') {
    $currenturl->param('returnurl', $returnurl);
    $returnurl = new url($returnurl);
} else {
    $returnurl = null;
}

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('reference_add', 'tool_mucatalog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$current = [
    'name' => $typeclass::get_reference_name($referenceid),
    'status' => util::STATUS_ACTIVE,
];
$form = new \tool_mucatalog\local\form\reference_add($currenturl, $current, ['typeclass' => $typeclass, 'referenceid' => $referenceid]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl ?? new url('/admin/tool/mucatalog/management/index.php'));
}

if ($data = $form->get_data()) {
    $section = $DB->get_record('tool_mucatalog_section', ['id' => $data->sectionid], '*', MUST_EXIST);
    require_capability('tool/mucatalog:manage', context::instance_by_id($section->contextid));

    $item = $typeclass::create((object)[
        'sectionid' => $section->id,
        'type' => $type,
        'referenceid' => $referenceid,
        'syncname' => 1,
        'hiddenbefore' => $data->hiddenbefore ?? null,
        'hiddenafter' => $data->hiddenafter ?? null,
        'status' => $data->status,
    ]);
    $handler->submitted($returnurl ?? new url('/admin/tool/mucatalog/management/item.php', ['id' => $item->id]));
}

$handler->render($form);
