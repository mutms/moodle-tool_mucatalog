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
 * Item entity.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mucatalog_item',
            'tool_mucatalog_section',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('item', 'tool_mucatalog');
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
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $itemalias = $this->get_table_alias('tool_mucatalog_item');
        $sectionalias = $this->get_table_alias('tool_mucatalog_section');
        $dateformat = get_string('strftimedatetimeshort');

        $columns[] = (new column(
            'name',
            new lang_string('item_name', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$itemalias}.name, {$itemalias}.id")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $name = format_string($row->name);
                return $name;
            });

        $columns[] = (new column(
            'namewithlink',
            new lang_string('item_name', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$itemalias}.name, {$itemalias}.id, {$sectionalias}.contextid")
            ->add_join("JOIN {tool_mucatalog_section} {$sectionalias} ON {$sectionalias}.id = {$itemalias}.sectionid")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }

                $name = format_string($row->name);
                $context = \context::instance_by_id($row->contextid);
                if (has_capability('tool/mucatalog:view', $context)) {
                    $url = new url('/admin/tool/mucatalog/management/item.php', ['id' => $row->id]);
                    $name = \html_writer::link($url, $name);
                }

                return $name;
            });

        $columns[] = (new column(
            'type',
            new lang_string('item_type', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$itemalias}.type")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value, \stdClass $row): string {
                if ($value === null) {
                    return '';
                }
                $types = \tool_mucatalog\local\item::get_type_names();
                return $types[$value] ?? get_string('error');
            });

        $columns[] = (new column(
            'reference',
            new lang_string('item_reference', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$itemalias}.id, {$itemalias}.type")
            ->add_join("JOIN {tool_mucatalog_section} {$sectionalias} ON {$sectionalias}.id = {$itemalias}.sectionid")
            ->set_is_sortable(false)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }

                $classname = \tool_mucatalog\local\item::get_type_classname($row->type);
                if (!$classname) {
                    return get_string('error');
                }

                return $classname::get_reference($row->id);
            });

        $columns[] = (new column(
            'status',
            new lang_string('item_status', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$itemalias}.status")
            ->set_is_sortable(true)
            ->add_callback(static function (?int $value, \stdClass $row): string {
                if ($value === null) {
                    return '';
                }
                $statuses = \tool_mucatalog\local\util::get_statuses_menu();
                return $statuses[$value];
            });

        $columns[] = (new column(
            'hiddenbefore',
            new lang_string('hiddenbefore', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$itemalias}.hiddenbefore")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'hiddenafter',
            new lang_string('hiddenafter', 'tool_mucatalog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$itemalias}.hiddenafter")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $itemalias = $this->get_table_alias('tool_mucatalog_item');

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('item_name', 'tool_mucatalog'),
            $this->get_entity_name(),
            "{$itemalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('item_status', 'tool_mucatalog'),
            $this->get_entity_name(),
            "{$itemalias}.status"
        ))
            ->add_joins($this->get_joins())
            ->set_options(\tool_mucatalog\local\util::get_statuses_menu());

        $filters[] = (new filter(
            select::class,
            'type',
            new lang_string('item_type', 'tool_mucatalog'),
            $this->get_entity_name(),
            "{$itemalias}.type"
        ))
            ->add_joins($this->get_joins())
            ->set_options(\tool_mucatalog\local\item::get_type_names());

        return $filters;
    }
}
