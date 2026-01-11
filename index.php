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
// phpcs:disable moodle.Files.RequireLogin.Missing

/**
 * Universal catalog.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_mucatalog\local\catalogue;

/** @var moodle_page $CFG */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */
/** @var moodle_database $USER */

require('../../../config.php');

$sectionid = optional_param('sectionid', null, PARAM_INT);

$syscontext = context_system::instance();

// If the site is currently under maintenance, then print a message.
if (!empty($CFG->maintenance_enabled)) {
    if (!has_capability('moodle/site:maintenanceaccess', $syscontext)) {
        print_maintenance_message();
    }
}

// Make sure site is upgraded when accessing catalogue as admin.
if (has_capability('moodle/site:config', $syscontext) && moodle_needs_upgrading()) {
    redirect(new url('/admin/index.php'));
}

if (!\tool_mulib\local\mulib::is_mucatalog_active()) {
    redirect(new url('/'));
}

if (!empty($CFG->forcelogin)) {
    require_login();
}
require_capability('tool/mucatalog:browse', $syscontext);

if (\tool_mulib\local\mulib::is_mutenancy_active()) {
    $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
} else {
    $tenantid = null;
}

$currenturl = new url('/admin/tool/mucatalog/');
$section = null;
$collection = null;
if ($sectionid > 0) {
    $section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionid]);
    if (!$section || !catalogue::is_section_visible($section, $USER->id, $tenantid)) {
        redirect(new url('/admin/tool/mucatalog/'));
    }
    $currenturl->param('sectionid', $sectionid);
} else if ($sectionid < 0) {
    $collection = $DB->get_record('tool_mucatalog_collection', ['id' => -1 * (int)$sectionid]);
    if (!$collection || !catalogue::is_collection_visible($collection, $USER->id, $tenantid)) {
        redirect(new url('/admin/tool/mucatalog/'));
    }
    $currenturl->param('sectionid', $sectionid);
} else if ($sectionid === 0) {
    $currenturl->param('sectionid', 0);
}

$catalogue = get_string('catalogue_title', 'tool_mucatalog');

$PAGE->set_context($syscontext);
$PAGE->set_url($currenturl);
$PAGE->set_cacheable(true);
$PAGE->set_secondary_navigation(false);
$PAGE->set_heading($catalogue);
$PAGE->set_title($catalogue);

$actions = new \tool_mulib\output\header_actions(get_string('actions'));

$viewcontext = null;
if (has_capability('tool/mucatalog:view', $syscontext)) {
    $viewcontext = $syscontext;
} else if ($section && $section->contextid != $syscontext->id) {
    $context = context::instance_by_id($section->contextid);
    if (has_capability('tool/mucatalog:view', $context)) {
        $viewcontext = $context;
    }
} else if ($collection && $collection->contextid != $syscontext->id) {
    $context = context::instance_by_id($collection->contextid);
    if (has_capability('tool/mucatalog:view', $context)) {
        $viewcontext = $context;
    }
}
if ($viewcontext) {
    $url = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $viewcontext->id]);
    $actions->get_dropdown()->add_item(get_string('management_sections', 'tool_mucatalog'), $url, new \core\output\pix_icon('i/menubars', ''));
    $url = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $viewcontext->id]);
    $actions->get_dropdown()->add_item(get_string('management_collections', 'tool_mucatalog'), $url, new \core\output\pix_icon('i/menubars', ''));
}

if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

if ($sectionid === null) {
    $frontpage = new \tool_mucatalog\output\frontpage();
    if ($frontpage->is_usable()) {
        $PAGE->add_body_class('limitedwidth');
        echo $OUTPUT->header();
        echo $OUTPUT->render($frontpage);
        echo $OUTPUT->footer();
        exit;
    }
    $sectionid = 0;
}

echo $OUTPUT->header();
echo $OUTPUT->render(new \tool_mucatalog\output\browse($sectionid));
echo $OUTPUT->footer();
