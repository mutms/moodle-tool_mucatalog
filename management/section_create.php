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
 * Add a new catalogue section.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_mucatalog\local\section;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require('../../../../config.php');

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid);

require_login();
require_capability('tool/mucatalog:manage', $context);

if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
    throw new moodle_exception('invalidcontext');
}

$currenturl = new url('/admin/tool/mucatalog/management/section_create.php', ['contextid' => $context->id]);
$returnurl = new url('/admin/tool/mucatalog/management/sections.php');

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('section_create', 'tool_mucatalog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$current = (array)section::get_defaults($context->id);
$current['frontpageshow'] = (int)($current['frontpagepriority'] !== null);

$form = new \tool_mucatalog\local\form\section_create($currenturl, $current, ['context' => $context]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    section::create($data);
    $returnurl = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $context->id]);
    $handler->submitted($returnurl);
}

$handler->render($form);
