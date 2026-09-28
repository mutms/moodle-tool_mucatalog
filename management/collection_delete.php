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
 * Delete catalog collection.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_mucatalog\local\collection;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$collection = $DB->get_record('tool_mucatalog_collection', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($collection->contextid, IGNORE_MISSING);
if (!$context) {
    $context = context_system::instance();
}

require_login();
require_capability('tool/mucatalog:manage', $context);

$currenturl = new url('/admin/tool/mucatalog/management/collection_delete.php', ['id' => $collection->id]);
$returnurl = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $context->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('collection_delete', 'tool_mucatalog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$form = new \tool_mucatalog\local\form\collection_delete($currenturl, $collection);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    collection::delete($collection->id);
    $handler->submitted($returnurl);
}

$handler->render($form);
