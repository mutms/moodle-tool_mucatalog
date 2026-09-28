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
use tool_mulib\muform\form;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require('../../../../config.php');

$sectionid = required_param('sectionid', PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHANUM);

$section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionid], '*', MUST_EXIST);
$context = context::instance_by_id($section->contextid);

require_login();
require_capability('tool/mucatalog:manage', $context);

$types = \tool_mucatalog\local\item::get_type_classnames();
foreach ($types as $t => $typeclassname) {
    if (!$typeclassname::is_available()) {
        unset($types[$t]);
    }
}
if (!isset($types[$type])) {
    $type = '';
}

$pageurl = new url('/admin/tool/mucatalog/management/items_create.php', ['sectionid' => $section->id]);
$currenturl = $type ? new url($pageurl, ['type' => $type]) : $pageurl;
$returnurl = new url('/admin/tool/mucatalog/management/section_items.php', ['id' => $section->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('items_create', 'tool_mucatalog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$createform = function (string $type, url $formurl) use ($types, $section): form {
    /** @var class-string<\tool_mucatalog\local\item> $itemclass */
    $itemclass = $types[$type];
    $formclass = $itemclass::get_create_form_class();
    $current = ['status' => util::STATUS_ACTIVE];
    return new $formclass($formurl, $current, ['section' => $section, 'typeclass' => $itemclass]);
};

if (!$type) {
    $form = new \tool_mucatalog\local\form\items_create_type($currenturl, [], ['section' => $section, 'types' => $types]);
    if ($form->is_cancelled()) {
        $handler->cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        $nexturl = new url($pageurl, ['type' => $data->type]);
        if ($handler->is_dialog()) {
            // The dialog continues with the second step, the page gets it from the next URL.
            $handler->render($createform($data->type, $nexturl));
        }
        redirect($nexturl);
    }
    $handler->render($form);
}

$form = $createform($type, $currenturl);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $data->sectionid = $section->id;
    $data->type = $type;
    $types[$type]::create_multiple($data);
    $handler->submitted($returnurl);
}

$handler->render($form);
