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

namespace tool_mucatalog\reportbuilder\local\systemreports;

use tool_mucatalog\reportbuilder\local\entities\section;
use core_reportbuilder\system_report;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\boolean_select;
use lang_string;
use core\url;

/**
 * Embedded sections report.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sections extends system_report {
    /** @var section */
    protected $sectionentity;
    /** @var string */
    protected $sectionalias;

    #[\Override]
    protected function initialise(): void {
        $this->sectionentity = new section();
        $this->sectionalias = $this->sectionentity->get_table_alias('tool_mucatalog_section');

        $this->set_main_table('tool_mucatalog_section', $this->sectionalias);
        $this->add_entity($this->sectionentity);

        $this->add_base_fields("{$this->sectionalias}.id, {$this->sectionalias}.contextid, {$this->sectionalias}.status");

        $this->add_join($this->sectionentity->get_context_join());

        // Make sure only sections from context and its subcontexts are shown.
        $context = $this->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            $this->add_join($this->sectionentity->get_context_map_join($context));
        }

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('error_nosections', 'tool_mucatalog'));
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('tool/mucatalog:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'section:name',
            'section:items',
            'section:context',
            'section:frontpageshow',
            'section:frontpagepriority',
            'section:guestvisible',
            'section:uservisible',
            'section:cohortvisible',
        ];
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $columns[] = 'section:hiddenfromtenants';
        }
        $columns[] = 'section:status';

        $this->add_columns_from_entities($columns);

        $this->set_initial_sort_column('section:name', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'section:name',
            'section:guestvisible',
            'section:uservisible',
            'section:status',
        ];
        $this->add_filters_from_entities($filters);
        $context = $this->get_context();

        $filter = new filter(
            boolean_select::class,
            'currentcontextonly',
            new lang_string('currentcontextonly', 'tool_mucatalog'),
            $this->sectionentity->get_entity_name(),
            "CASE WHEN {$this->sectionalias}.contextid = {$context->id} THEN 1 ELSE 0 END"
        );
        $this->add_filter($filter);
    }

    /**
     * Row class.
     *
     * @param \stdClass $row
     * @return string
     */
    public function get_row_class(\stdClass $row): string {
        return ($row->status != \tool_mucatalog\local\util::STATUS_ACTIVE) ? 'text-muted' : '';
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        global $SCRIPT;

        // Report builder download script is missing NO_DEBUG_DISPLAY
        // and template rendering is changing session after it is closed,
        // add a hacky workaround for now.
        if ($SCRIPT === '/reportbuilder/download.php') {
            return;
        }

        $url = new url('/admin/tool/mucatalog/management/section_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('section_update', 'tool_mucatalog'), 'i/edit');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if (!$context) {
                    return false;
                }

                return has_capability('tool/mucatalog:manage', $context);
            }));

        $url = new url('/admin/tool/mucatalog/management/section_activate.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('section_activate', 'tool_mucatalog'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if (!$context) {
                    return false;
                }
                if (!\tool_mucatalog\local\section::is_activate_possible($row)) {
                    return false;
                }

                return has_capability('tool/mucatalog:manage', $context);
            }));

        $url = new url('/admin/tool/mucatalog/management/section_archive.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('section_archive', 'tool_mucatalog'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if (!$context) {
                    return false;
                }
                if (!\tool_mucatalog\local\section::is_archive_possible($row)) {
                    return false;
                }

                return has_capability('tool/mucatalog:manage', $context);
            }));

        $url = new url('/admin/tool/mucatalog/management/section_restore.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('section_restore', 'tool_mucatalog'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if (!$context) {
                    return false;
                }
                if (!\tool_mucatalog\local\section::is_restore_possible($row)) {
                    return false;
                }

                return has_capability('tool/mucatalog:manage', $context);
            }));

        $url = new url('/admin/tool/mucatalog/management/section_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('section_delete', 'tool_mucatalog'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if (!$context) {
                    $context = \context_system::instance();
                }
                if (!\tool_mucatalog\local\section::is_delete_possible($row)) {
                    return false;
                }

                return has_capability('tool/mucatalog:manage', $context);
            }));
    }
}
