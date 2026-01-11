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

namespace tool_mucatalog\reportbuilder\local\entities;

use core\url;
use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\select;

/**
 * Collection entity.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mucatalog_collection',
            'tool_mucatalog_collection_item',
            'context',
            'tool_mulib_context_map',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('collection', 'tool_mucatalog');
    }

    #[\Override]
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Return syntax for joining on the context table
     *
     * @return string
     */
    public function get_context_join(): string {
        $collectionalias = $this->get_table_alias('tool_mucatalog_collection');
        $contextalias = $this->get_table_alias('context');

        return "JOIN {context} {$contextalias} ON {$contextalias}.id = {$collectionalias}.contextid";
    }

    /**
     * Return syntax for joining on the context map table to restrict result to subcontexts.
     *
     * @param \context $context
     * @return string
     */
    public function get_context_map_join(\context $context): string {
        $collectionalias = $this->get_table_alias('tool_mucatalog_collection');
        $contextmapalias = $this->get_table_alias('tool_mulib_context_map');

        return "JOIN {tool_mulib_context_map} {$contextmapalias} ON
                     {$contextmapalias}.contextid = {$collectionalias}.contextid AND {$contextmapalias}.relatedcontextid = {$context->id}";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $collectionalias = $this->get_table_alias('tool_mucatalog_collection');

        $columns[] = (new column(
            'name',
            new lang_string('collection_name', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$collectionalias}.name, {$collectionalias}.id, {$collectionalias}.contextid")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = format_string($row->name);
                if (has_capability('tool/mucatalog:view', $context)) {
                    $url = new url('/admin/tool/mucatalog/management/collection.php', ['id' => $row->id]);
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });

        $columns[] = (new column(
            'frontpagepriority',
            new lang_string('frontpagepriority', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$collectionalias}.frontpagepriority")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'guestvisible',
            new lang_string('guestvisible', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$collectionalias}.guestvisible")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'uservisible',
            new lang_string('uservisible', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$collectionalias}.uservisible")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'hiddenfromtenants',
            new lang_string('hiddenfromtenants', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$collectionalias}.hiddenfromtenants")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'context',
            new lang_string('collection_category', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_context_join())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$collectionalias}.contextid")
            ->set_is_sortable(false)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$row->contextid) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = $context->get_context_name(false);

                if (!has_capability('tool/mucatalog:view', $context)) {
                    return $name;
                }
                $url = new url('/admin/tool/mucatalog/management/collections.php', ['contextid' => $context->id]);
                $name = \html_writer::link($url, $name);
                return $name;
            });

        $columns[] = (new column(
            'cohortvisible',
            new lang_string('cohortvisible', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$collectionalias}.id")
            ->add_field("{$collectionalias}.uservisible")
            ->set_is_sortable(false)
            ->add_callback(static function (?int $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                if ($row->uservisible) {
                    return '-';
                }
                $cohorts = \tool_mucatalog\local\collection::get_cohortvisible_menu($row->id);
                if (!$cohorts) {
                    return '-';
                }
                $cohorts = array_map('format_string', $cohorts);
                return implode(', ', $cohorts);
            });

        $columns[] = (new column(
            'items',
            new lang_string('items', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$collectionalias}.id, {$collectionalias}.contextid")
            ->set_is_sortable(false)
            ->add_callback(static function (?int $value, \stdClass $row): string {
                global $DB;

                if (!$row->id) {
                    return '';
                }

                $count = $DB->count_records('tool_mucatalog_collection_item', ['collectionid' => $row->id]);

                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if ($context && has_capability('tool/mucatalog:view', $context)) {
                    $url = new url('/admin/tool/mucatalog/management/collection_items.php', ['id' => $row->id]);
                    $count = \html_writer::link($url, $count);
                }

                return $count;
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $collectionalias = $this->get_table_alias('tool_mucatalog_collection');

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('collection_name', 'tool_mucatalog'),
            $this->get_entity_name(),
            "{$collectionalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'guestvisible',
            new lang_string('guestvisible', 'tool_mucatalog'),
            $this->get_entity_name(),
            "{$collectionalias}.guestvisible"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'uservisible',
            new lang_string('uservisible', 'tool_mucatalog'),
            $this->get_entity_name(),
            "{$collectionalias}.uservisible"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
