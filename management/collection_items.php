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
 * Catalogue collection items management page.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucatalog\local\management;
use core\url;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$collection = $DB->get_record('tool_mucatalog_collection', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($collection->contextid);

require_login();
require_capability('tool/mucatalog:view', $context);

$currenturl = new url('/admin/tool/mucatalog/management/collection_items.php', ['id' => $collection->id]);

management::setup_collection_page($currenturl, $context, $collection, 'collection_items');

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_mucatalog\reportbuilder\local\systemreports\collection_items::class,
    $context,
    '',
    '',
    0,
    ['collectionid' => $collection->id]
);
echo $report->output();

echo $OUTPUT->footer();
