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

namespace tool_mucatalog\local;

use core\url;
use tool_mulib\output\header_actions;
use tool_mulib\output\ajax_form\button;
use tool_mulib\local\sql;
use stdClass;

/**
 * Catalogue sections management UI helper.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Set up $PAGE and navigation for sections management page.
     *
     * @param url $pageurl
     * @param \context $context
     */
    public static function setup_sections_page(url $pageurl, \context $context): void {
        global $PAGE, $OUTPUT;

        $syscontext = \context_system::instance();

        $title = get_string('management_sections', 'tool_mucatalog');

        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_pagelayout('admin');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $PAGE->set_secondary_navigation(false);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('management_sections', 'tool_mucatalog');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/mucatalog:view', $parentcontext)) {
                $url = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $actions = new header_actions(get_string('management_actions', 'tool_mucatalog'));
        if (has_capability('tool/mucatalog:managesections', $context)) {
            $url = new url('/admin/tool/mucatalog/management/section_create.php', ['contextid' => $context->id]);
            $button = new button($url, get_string('section_create', 'tool_mucatalog'));
            $actions->add_button($button);
        }
        if (has_capability('moodle/site:config', $syscontext)) {
            $url = new url('/admin/settings.php', ['section' => 'tool_mucatalog_settings']);
            $actions->get_dropdown()->add_item(get_string('settings', 'tool_mucatalog'), $url, new \core\output\pix_icon('i/settings', ''));
        }
        if (has_capability('tool/mucatalog:view', $context)) {
            $url = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $context->id]);
            $actions->get_dropdown()->add_item(get_string('management_collections', 'tool_mucatalog'), $url, new \core\output\pix_icon('i/menubars', ''));
        }

        if ($actions->has_items()) {
            $PAGE->add_header_action($OUTPUT->render($actions));
        }
    }

    /**
     * Set up $PAGE and navigation for collections management page.
     *
     * @param url $pageurl
     * @param \context $context
     */
    public static function setup_collections_page(url $pageurl, \context $context): void {
        global $PAGE, $OUTPUT;

        $syscontext = \context_system::instance();

        $title = get_string('management_collections', 'tool_mucatalog');

        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_pagelayout('admin');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $PAGE->set_secondary_navigation(false);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('management_collections', 'tool_mucatalog');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/mucatalog:view', $parentcontext)) {
                $url = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $actions = new header_actions(get_string('management_actions', 'tool_mucatalog'));
        if (has_capability('tool/mucatalog:managecollections', $context)) {
            $url = new url('/admin/tool/mucatalog/management/collection_create.php', ['contextid' => $context->id]);
            $button = new button($url, get_string('collection_create', 'tool_mucatalog'));
            $actions->add_button($button);
        }
        if (has_capability('moodle/site:config', $syscontext)) {
            $url = new url('/admin/settings.php', ['section' => 'tool_mucatalog_settings']);
            $actions->get_dropdown()->add_item(get_string('settings', 'tool_mucatalog'), $url, new \core\output\pix_icon('i/settings', ''));
        }
        if (has_capability('tool/mucatalog:view', $context)) {
            $url = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $context->id]);
            $actions->get_dropdown()->add_item(get_string('management_sections', 'tool_mucatalog'), $url, new \core\output\pix_icon('i/menubars', ''));
        }

        if ($actions->has_items()) {
            $PAGE->add_header_action($OUTPUT->render($actions));
        }
    }

    /**
     * Set up $PAGE and navigation for individual section pages.
     *
     * @param url $pageurl
     * @param \context $context
     * @param stdClass $section
     * @param string $secondarytab
     */
    public static function setup_section_page(url $pageurl, \context $context, stdClass $section, string $secondarytab): void {
        global $PAGE, $OUTPUT;

        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);

        $sectionname = format_string($section->name);

        $PAGE->set_pagelayout('admin');
        $PAGE->set_title($sectionname . \moodle_page::TITLE_SEPARATOR . get_string('management_sections', 'tool_mucatalog'));
        $PAGE->set_heading($sectionname);

        $secondarynav = new \tool_mucatalog\navigation\views\section_secondary($PAGE, $section);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($secondarytab);
        $secondarynav->initialise();

        $PAGE->set_secondary_navigation(true);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('management_sections', 'tool_mucatalog');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/mucatalog:view', $parentcontext)) {
                $url = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $PAGE->navbar->add($sectionname, $pageurl);

        $actions = new header_actions(get_string('section_actions', 'tool_mucatalog'));

        if ($secondarytab === 'section_items' && has_capability('tool/mucatalog:manageitems', $context)) {
            if ($section->status != util::STATUS_ARCHIVED) {
                $url = new url('/admin/tool/mucatalog/management/items_create.php', ['sectionid' => $section->id]);
                $button = new button($url, get_string('items_create', 'tool_mucatalog'));
                $actions->add_button($button);
            }
        }

        if ($secondarytab === 'section_general' && has_capability('tool/mucatalog:managesections', $context)) {
            if (section::is_delete_possible($section)) {
                $url = new \core\url('/admin/tool/mucatalog/management/section_delete.php', ['id' => $section->id]);
                $link = new \tool_mulib\output\ajax_form\link($url, get_string('section_delete', 'tool_mucatalog'), 'i/delete');
                $link->add_class('text-danger');
                $link->set_form_size('sm');
                $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
                $actions->get_dropdown()->add_ajax_form($link);
            }
        }

        if ($actions->has_items()) {
            $PAGE->add_header_action($OUTPUT->render($actions));
        }
    }

    /**
     * Set up $PAGE and navigation for individual section pages.
     *
     * @param url $pageurl
     * @param \context $context
     * @param stdClass $item
     * @param stdClass $section
     */
    public static function setup_item_page(url $pageurl, \context $context, stdClass $item, stdClass $section): void {
        global $PAGE;

        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);

        $sectionname = format_string($section->name);
        $itemname = format_string($item->name);
        $titles = [$itemname, $sectionname, get_string('management_sections', 'tool_mucatalog')];

        $PAGE->set_pagelayout('admin');
        $PAGE->set_title(implode(\moodle_page::TITLE_SEPARATOR, $titles));
        $PAGE->set_heading($itemname);

        $PAGE->set_secondary_navigation(false);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('management_sections', 'tool_mucatalog');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/mucatalog:view', $parentcontext)) {
                $url = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $url = new url('/admin/tool/mucatalog/management/section.php', ['id' => $section->id]);
        $PAGE->navbar->add($sectionname, $url);

        $url = new url('/admin/tool/mucatalog/management/item.php', ['id' => $item->id]);
        $PAGE->navbar->add($itemname, $url);
    }

    /**
     * Set up $PAGE and navigation for individual collection pages.
     *
     * @param url $pageurl
     * @param \context $context
     * @param stdClass $collection
     * @param string $secondarytab
     */
    public static function setup_collection_page(url $pageurl, \context $context, stdClass $collection, string $secondarytab): void {
        global $PAGE, $OUTPUT;

        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);

        $collectionname = format_string($collection->name);

        $PAGE->set_pagelayout('admin');
        $PAGE->set_title($collectionname . \moodle_page::TITLE_SEPARATOR . get_string('management_collections', 'tool_mucatalog'));
        $PAGE->set_heading($collectionname);

        $secondarynav = new \tool_mucatalog\navigation\views\collection_secondary($PAGE, $collection);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($secondarytab);
        $secondarynav->initialise();

        $PAGE->set_secondary_navigation(true);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('management_collections', 'tool_mucatalog');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/mucatalog:view', $parentcontext)) {
                $url = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $PAGE->navbar->add($collectionname, $pageurl);

        $actions = new header_actions(get_string('collection_actions', 'tool_mucatalog'));

        if ($secondarytab === 'collection_items' && has_capability('tool/mucatalog:managecollections', $context)) {
            $url = new url('/admin/tool/mucatalog/management/collection_items_add.php', ['collectionid' => $collection->id]);
            $button = new button($url, get_string('collection_items_add', 'tool_mucatalog'));
            $actions->add_button($button);
        }

        if ($secondarytab === 'collection_general' && has_capability('tool/mucatalog:managecollections', $context)) {
            $url = new \core\url('/admin/tool/mucatalog/management/collection_delete.php', ['id' => $collection->id]);
            $link = new \tool_mulib\output\ajax_form\link($url, get_string('collection_delete', 'tool_mucatalog'), 'i/delete');
            $link->add_class('text-danger');
            $link->set_form_size('sm');
            $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
            $actions->get_dropdown()->add_ajax_form($link);
        }

        if ($actions->has_items()) {
            $PAGE->add_header_action($OUTPUT->render($actions));
        }
    }
}
