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
 * Update catalogue section.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_mucatalog\local\section;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$section = $DB->get_record('tool_mucatalog_section', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($section->contextid);

require_login();
require_capability('tool/mucatalog:managesections', $context);

$currenturl = new url('/admin/tool/mucatalog/management/section_update.php', ['id' => $section->id]);
$returnurl = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $context->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$section->cohortvisible = array_keys(section::get_cohortvisible_menu($section->id));

$form = new \tool_mucatalog\local\form\section_update(
    null,
    ['currentdata' => $section, 'context' => $context]
);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $section = section::update($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
