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
 * Add existing item to collection.
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

$collectionid = required_param('collectionid', PARAM_INT);

$collection = $DB->get_record('tool_mucatalog_collection', ['id' => $collectionid], '*', MUST_EXIST);
$context = context::instance_by_id($collection->contextid);

require_login();
require_capability('tool/mucatalog:managecollections', $context);

$currenturl = new url('/admin/tool/mucatalog/management/collection_items_add.php', ['collectionid' => $collection->id]);
$returnurl = new url('/admin/tool/mucatalog/management/collection_items.php', ['id' => $collection->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$currentdata = (object)[
    'collectionid' => $collection->id,
];

$form = new \tool_mucatalog\local\form\collection_items_add(null, ['collection' => $collection, 'context' => $context, 'currentdata' => $currentdata]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    foreach ($data->itemids as $itemid) {
        $itemdata = clone($data);
        $itemdata->itemid = $itemid;
        \tool_mucatalog\local\collection::add_item($data->collectionid, $itemid);
    }

    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
