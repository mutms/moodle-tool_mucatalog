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
 * Update catalogue collection.
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
$context = context::instance_by_id($collection->contextid);

require_login();
require_capability('tool/mucatalog:manage', $context);

$currenturl = new url('/admin/tool/mucatalog/management/collection_update.php', ['id' => $collection->id]);
$returnurl = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $context->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('collection_update', 'tool_mucatalog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$current = (array)$collection;
$current['frontpageshow'] = (int)($collection->frontpagepriority !== null);
$current['cohortvisible'] = array_keys(collection::get_cohortvisible_menu($collection->id));

$form = new \tool_mucatalog\local\form\collection_update($currenturl, $current, ['context' => $context]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $data->id = $collection->id;
    collection::update($data);
    $handler->submitted($returnurl);
}

$handler->render($form);
