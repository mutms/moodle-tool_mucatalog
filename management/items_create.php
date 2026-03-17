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
 * Add a new item to catalogue section.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_mucatalog\local\util;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$sectionid = required_param('sectionid', PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHANUM);

$section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionid], '*', MUST_EXIST);
$context = context::instance_by_id($section->contextid);

require_login();
require_capability('tool/mucatalog:manage', $context);

$currenturl = new url('/admin/tool/mucatalog/management/items_create.php', ['sectionid' => $section->id]);
$returnurl = new url('/admin/tool/mucatalog/management/section_items.php', ['id' => $section->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$types = \tool_mucatalog\local\item::get_type_classnames();
foreach ($types as $t => $typeclassname) {
    if (!$typeclassname::is_available()) {
        unset($types[$t]);
    }
}

if (!isset($types[$type])) {
    $type = '';
}

if (!$type) {
    $form = new \tool_mucatalog\local\form\items_create_type(null, ['section' => $section, 'context' => $context, 'types' => $types]);
    if ($form->is_cancelled()) {
        $form->ajax_form_cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        if (isset($types[$data->type])) {
            $type = $types[$data->type];
        }
    }
    if (!$type) {
        $form->ajax_form_render();
    }
}

/** @var \tool_mucatalog\local\item $itemclass */
$itemclass = $types[$type];
$formclass = $itemclass::get_create_form_class();

$currentdata = (object)[
    'sectionid' => $section->id,
    'type' => $type,
    'status' => util::STATUS_ACTIVE,
];

$form = new $formclass(null, ['section' => $section, 'context' => $context, 'currentdata' => $currentdata]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $item = $itemclass::create_multiple($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
