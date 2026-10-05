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

namespace tool_mucatalog\output\management;

use tool_mucatalog\local\util;
use tool_mucatalog\local\section;
use tool_mucatalog\local\collection;
use tool_mucatalog\local\item;
use core\url, stdClass, html_writer;

/**
 * Catalogue section management renderer.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render section.
     *
     * @param stdClass $section
     * @return string
     */
    public function render_section_general(stdClass $section): string {
        $context = \context::instance_by_id($section->contextid);

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('section_name', 'tool_mucatalog'), format_string($section->name));

        if (trim($section->shortdescription) !== '') {
            $description = format_text($section->shortdescription, FORMAT_MARKDOWN);
            $details->add(get_string('description'), $description);
        }

        $categoryname = $context->get_context_name(false);
        $url = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $context->id]);
        $categoryname = html_writer::link($url, $categoryname);
        if (has_capability('tool/mucatalog:manage', $context)) {
            $url = new url('/admin/tool/mucatalog/management/section_move.php', ['id' => $section->id]);
            $link = new \tool_mulib\output\muform\dialog\icon($url, get_string('section_move', 'tool_mucatalog'), 'i/edit');
            $categoryname .= $this->output->render($link);
        }
        $details->add(get_string('section_category', 'tool_mucatalog'), $categoryname);

        if ($section->frontpagepriority) {
            $details->add(get_string('frontpagepriority', 'tool_mucatalog'), $section->frontpagepriority);
        }

        $details->add(get_string('guestvisible', 'tool_mucatalog'), ($section->guestvisible ? get_string('yes') : get_string('no')));

        $details->add(get_string('uservisible', 'tool_mucatalog'), ($section->uservisible ? get_string('yes') : get_string('no')));
        if (!$section->uservisible) {
            $cohorts = section::get_cohortvisible_menu($section->id);
            if ($cohorts) {
                $cohrotsstr = implode(', ', array_map('format_string', $cohorts));
            } else {
                $cohrotsstr = '-';
            }
            $details->add(get_string('cohortvisible', 'tool_mucatalog'), $cohrotsstr);
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $details->add(get_string('hiddenfromtenants', 'tool_mucatalog'), ($section->hiddenfromtenants ? get_string('yes') : get_string('no')));
        }

        $statuses = util::get_statuses_menu();
        $status = $statuses[$section->status];
        if (has_capability('tool/mucatalog:manage', $context)) {
            $action = null;
            if (section::is_activate_possible($section)) {
                $url = new url('/admin/tool/mucatalog/management/section_activate.php', ['id' => $section->id]);
                $action = new \tool_mulib\output\muform\dialog\icon($url, get_string('section_activate', 'tool_mucatalog'), 'i/settings');
            }
            if (section::is_restore_possible($section)) {
                $url = new url('/admin/tool/mucatalog/management/section_restore.php', ['id' => $section->id]);
                $action = new \tool_mulib\output\muform\dialog\icon($url, get_string('section_restore', 'tool_mucatalog'), 'i/settings');
            }
            if (section::is_archive_possible($section)) {
                $url = new url('/admin/tool/mucatalog/management/section_archive.php', ['id' => $section->id]);
                $action = new \tool_mulib\output\muform\dialog\icon($url, get_string('section_archive', 'tool_mucatalog'), 'i/settings');
            }
            if ($action) {
                $action->set_form_size('sm');
                $status .= $this->output->render($action);
            }
        }
        $details->add(get_string('section_status', 'tool_mucatalog'), $status);

        $result = $this->output->render($details);

        return $result;
    }

    /**
     * Render collection.
     *
     * @param stdClass $collection
     * @return string
     */
    public function render_collection_general(stdClass $collection): string {
        $context = \context::instance_by_id($collection->contextid);

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('collection_name', 'tool_mucatalog'), format_string($collection->name));

        if (trim($collection->shortdescription) !== '') {
            $description = format_text($collection->shortdescription, FORMAT_MARKDOWN);
            $details->add(get_string('description'), $description);
        }

        $categoryname = $context->get_context_name(false);
        $url = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $context->id]);
        $categoryname = html_writer::link($url, $categoryname);
        if (has_capability('tool/mucatalog:manage', $context)) {
            $url = new url('/admin/tool/mucatalog/management/collection_move.php', ['id' => $collection->id]);
            $link = new \tool_mulib\output\muform\dialog\icon($url, get_string('collection_move', 'tool_mucatalog'), 'i/edit');
            $categoryname .= $this->output->render($link);
        }
        $details->add(get_string('collection_category', 'tool_mucatalog'), $categoryname);

        if ($collection->frontpagepriority) {
            $details->add(get_string('frontpagepriority', 'tool_mucatalog'), $collection->frontpagepriority);
        }

        $details->add(get_string('guestvisible', 'tool_mucatalog'), ($collection->guestvisible ? get_string('yes') : get_string('no')));

        $details->add(get_string('uservisible', 'tool_mucatalog'), ($collection->uservisible ? get_string('yes') : get_string('no')));
        if (!$collection->uservisible) {
            $cohorts = collection::get_cohortvisible_menu($collection->id);
            if ($cohorts) {
                $cohrotsstr = implode(', ', array_map('format_string', $cohorts));
            } else {
                $cohrotsstr = '-';
            }
            $details->add(get_string('cohortvisible', 'tool_mucatalog'), $cohrotsstr);
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $details->add(get_string('hiddenfromtenants', 'tool_mucatalog'), ($collection->hiddenfromtenants ? get_string('yes') : get_string('no')));
        }

        $result = $this->output->render($details);

        return $result;
    }

    /**
     * Render catalogue item.
     *
     * @param stdClass $item
     * @param stdClass $section
     * @return string
     */
    public function render_item(stdClass $item, stdClass $section): string {
        $context = \context::instance_by_id($section->contextid);

        $classname = item::get_type_classname($item->type);
        if (!$classname) {
            return get_string('error');
        }

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('section', 'tool_mucatalog'), format_string($section->name));

        $details->add($classname::get_type_name(), $classname::get_reference($item->id));
        $details->add(get_string('item_syncname', 'tool_mucatalog'), $item->syncname ? get_string('yes') : get_string('no'));

        $details->add(
            get_string('hiddenbefore', 'tool_mucatalog'),
            $item->hiddenbefore ? userdate($item->hiddenbefore) : get_string('notset', 'tool_mulib')
        );

        $details->add(
            get_string('hiddenafter', 'tool_mucatalog'),
            $item->hiddenafter ? userdate($item->hiddenafter) : get_string('notset', 'tool_mulib')
        );

        $collections = collection::get_item_collections($item->id);
        if ($collections) {
            $menu = [];
            foreach ($collections as $collection) {
                $colname = format_string($collection->name);
                $colcontext = \context::instance_by_id($collection->contextid, IGNORE_MISSING);
                if ($colcontext) {
                    if (has_capability('tool/mucatalog:view', $colcontext)) {
                        $url = new url('/admin/tool/mucatalog/management/collection.php', ['id' => $collection->id]);
                        $colname = html_writer::link($url, $colname);
                    }
                }
                $menu[$collection->id] = $colname;
            }
            \core_collator::asort($menu, \core_collator::SORT_NATURAL);
            $details->add(get_string('collections', 'tool_mucatalog'), implode(', ', $menu));
        }

        $statuses = util::get_statuses_menu();
        $status = $statuses[$item->status];
        if (has_capability('tool/mucatalog:manage', $context)) {
            $action = null;
            if ($classname::is_activate_possible($item)) {
                $url = new url('/admin/tool/mucatalog/management/item_activate.php', ['id' => $item->id]);
                $action = new \tool_mulib\output\muform\dialog\icon($url, get_string('item_activate', 'tool_mucatalog'), 'i/settings');
            }
            if ($classname::is_restore_possible($item)) {
                $url = new url('/admin/tool/mucatalog/management/item_restore.php', ['id' => $item->id]);
                $action = new \tool_mulib\output\muform\dialog\icon($url, get_string('item_restore', 'tool_mucatalog'), 'i/settings');
            }
            if ($classname::is_archive_possible($item)) {
                $url = new url('/admin/tool/mucatalog/management/item_archive.php', ['id' => $item->id]);
                $action = new \tool_mulib\output\muform\dialog\icon($url, get_string('item_archive', 'tool_mucatalog'), 'i/settings');
            }
            if ($action) {
                $action->set_form_size('sm');
                $status .= $this->output->render($action);
            }
        }
        $details->add(get_string('item_status', 'tool_mucatalog'), $status);

        return $this->output->render($details);
    }

    /**
     * Render list of catalogue sections that include given reference.
     *
     * NOTE: this is intended for management pages of plugins that are referenced from catalogue.
     *
     * @param string $type item type
     * @param int $referenceid
     * @return string
     */
    public function render_reference_sections(string $type, int $referenceid): string {
        $records = \tool_mucatalog\local\management::get_reference_sections($type, $referenceid);
        if (!$records) {
            return $this->output->notification(get_string('reference_sections_none', 'tool_mucatalog'), 'info', false);
        }

        $statuses = util::get_statuses_menu();
        $dateformat = get_string('strftimedatetimeshort', 'langconfig');

        $table = new \html_table();
        $table->id = 'tool_mucatalog_reference_sections';
        $table->attributes['class'] = 'table table-striped table-hover table-bordered';
        $table->head = [
            get_string('section', 'tool_mucatalog'),
            get_string('section_category', 'tool_mucatalog'),
            get_string('section_status', 'tool_mucatalog'),
            get_string('audience', 'tool_mucatalog'),
            get_string('item_status', 'tool_mucatalog'),
            get_string('hiddenbefore', 'tool_mucatalog'),
            get_string('hiddenafter', 'tool_mucatalog'),
        ];
        $table->data = [];

        foreach ($records as $record) {
            $context = \context::instance_by_id($record->sectioncontextid, IGNORE_MISSING);
            $canview = ($context && has_capability('tool/mucatalog:view', $context));

            $sectionname = format_string($record->sectionname);
            $itemstatus = $statuses[$record->status];
            if ($canview) {
                $url = new url('/admin/tool/mucatalog/management/section.php', ['id' => $record->sectionid]);
                $sectionname = html_writer::link($url, $sectionname);
                $url = new url('/admin/tool/mucatalog/management/item.php', ['id' => $record->id]);
                $itemstatus = html_writer::link($url, $itemstatus);
            }

            $audience = [];
            if ($record->sectionguestvisible) {
                $audience[] = get_string('audience_guests', 'tool_mucatalog');
            }
            if ($record->sectionuservisible) {
                $audience[] = get_string('audience_allusers', 'tool_mucatalog');
            } else {
                $cohorts = section::get_cohortvisible_menu($record->sectionid);
                foreach ($cohorts as $cohortname) {
                    $audience[] = format_string($cohortname);
                }
            }

            $table->data[] = [
                $sectionname,
                $context ? $context->get_context_name(false) : '',
                $statuses[$record->sectionstatus],
                $audience ? implode(', ', $audience) : '-',
                $itemstatus,
                $record->hiddenbefore ? userdate($record->hiddenbefore, $dateformat) : '-',
                $record->hiddenafter ? userdate($record->hiddenafter, $dateformat) : '-',
            ];
        }

        return html_writer::table($table);
    }
}
