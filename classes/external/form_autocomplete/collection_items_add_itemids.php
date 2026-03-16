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

namespace tool_mucatalog\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\sql;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mucatalog\local\util;
use stdClass;

/**
 * Add items to collection.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection_items_add_itemids extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null tool_mucatalog_item table */
    protected const ITEM_TABLE = 'tool_mucatalog_item';
    /** @var string|null field used for item name */
    protected const ITEM_FIELD = 'name';

    #[\Override]
    public static function get_multiple(): bool {
        return true;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'collectionid' => new external_value(PARAM_INT, 'Collection id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds candidates for adding items to collection.
     *
     * @param string $query The search request.
     * @param int $collectionid The collection.
     * @return array
     */
    public static function execute(string $query, int $collectionid): array {
        global $DB, $USER;

        [
            'query' => $query,
            'collectionid' => $collectionid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'collectionid' => $collectionid,
        ]);

        $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $collectionid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($collection->contextid);
        self::validate_context($context);
        require_capability('tool/mucatalog:manage', $context);

        $capjoin = context_map::get_contexts_by_capability_join('tool/mucatalog:manage', $USER->id, 'ctx');

        $sql = (
            new sql(
                "SELECT i.id, i.name, i.type, c.name AS sectionname
                   FROM {tool_mucatalog_item} i
                   JOIN {tool_mucatalog_section} c ON c.id = i.sectionid AND c.status = :active1
                   JOIN {context} ctx ON ctx.id = c.contextid
                   /* capjoin */
              LEFT JOIN {tool_mucatalog_collection_item} ei ON ei.collectionid = :collectionid AND ei.itemid = i.id
                  WHERE ei.id IS NULL AND i.status = :active2
                        /* capwhere */ /* searchsql */ /* tenantwhere */
               GROUP BY i.id, i.name, i.type, c.name
               ORDER BY i.name ASC, c.name ASC",
                ['collectionid' => $collection->id, 'active1' => util::STATUS_ACTIVE, 'active2' => util::STATUS_ACTIVE]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                self::get_search_query($query, ['name'], 'i')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantwhere',
                    new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$context->tenantid])
                );
            }
        }

        $items = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($items, $context);
    }

    /**
     * Format user label for display.
     *
     * NOTE: there is no need to change format_list() because this is not used for editing.
     *
     * @param stdClass $item
     * @param \context $context
     * @return string HTML fragment
     */
    public static function format_label(stdClass $item, \context $context): string {
        $parts = [];
        $parts[] = parent::format_label($item, $context);

        $classname = \tool_mucatalog\local\item::get_type_classname($item->type);
        if ($classname) {
            $parts[] = $classname::get_type_name();
        } else {
            $parts[] = get_string('error');
        }

        if (isset($item->sectionname)) {
            $parts[] = format_string($item->sectionname);
        }

        return implode(\moodle_page::TITLE_SEPARATOR, $parts);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $args['collectionid']], '*', MUST_EXIST);
        if ($collection->contextid != $context->id) {
            debugging('collection contextid parameter mismatch', DEBUG_DEVELOPER);
            return get_string('error');
        }

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $value]);
        if (!$item || $item->status != util::STATUS_ACTIVE) {
            return get_string('error');
        }
        $section = $DB->get_record('tool_mucatalog_section', ['id' => $item->sectionid]);
        if (!$section || $section->status != util::STATUS_ACTIVE) {
            return get_string('error');
        }

        if ($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection->id, 'itemid' => $item->id])) {
            return get_string('error');
        }

        $itemcontext = \context::instance_by_id($section->contextid);
        if (!has_capability('tool/mucatalog:manage', $itemcontext)) {
            return get_string('error');
        }

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                if ($itemcontext->tenantid && $itemcontext->tenantid != $context->tenantid) {
                    return get_string('error');
                }
            }
        }

        return null;
    }
}
