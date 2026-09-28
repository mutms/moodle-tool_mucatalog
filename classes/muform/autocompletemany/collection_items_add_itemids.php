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

namespace tool_mucatalog\muform\autocompletemany;

use tool_mucatalog\local\util;
use tool_mulib\local\search_util;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Active section items that may be added to a collection.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection_items_add_itemids extends \tool_mulib\muform\autocompletemany\base {
    /** @var string capability required in the item section context */
    private const string CAPABILITY = 'tool/mucatalog:manage';

    /** @var \context collection context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $collectionid
     */
    public function __construct(
        /** @var int collection id */
        private readonly int $collectionid
    ) {
        global $DB;
        $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $collectionid], '*', MUST_EXIST);
        $this->context = \context::instance_by_id($collection->contextid);
        require_capability(self::CAPABILITY, $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->collectionid];
    }

    #[\Override]
    public function search(string $query, int $maxitems, array $exclude): ?array {
        global $DB, $USER;

        $capjoin = context_map::get_contexts_by_capability_join(self::CAPABILITY, $USER->id, 'ctx');

        $sql = (
            new sql(
                "SELECT i.id, i.name, i.type, s.name AS sectionname, s.contextid
                   FROM {tool_mucatalog_item} i
                   JOIN {tool_mucatalog_section} s ON s.id = i.sectionid AND s.status = :active1
                   JOIN {context} ctx ON ctx.id = s.contextid
                   /* capjoin */
              LEFT JOIN {tool_mucatalog_collection_item} ci ON ci.collectionid = :collectionid AND ci.itemid = i.id
                  WHERE ci.id IS NULL AND i.status = :active2
                        /* capwhere */ /* searchsql */ /* tenantwhere */ /* exclude */
               GROUP BY i.id, i.name, i.type, s.name, s.contextid
               ORDER BY i.name ASC, s.name ASC, i.id ASC",
                ['collectionid' => $this->collectionid, 'active1' => util::STATUS_ACTIVE, 'active2' => util::STATUS_ACTIVE]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                search_util::get_search_query(trim($query), ['name'], 'i')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active() && $this->context->tenantid) {
            $sql = $sql->replace_comment(
                'tenantwhere',
                new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$this->context->tenantid])
            );
        }

        $exclude = array_values(array_filter($exclude, fn($id) => is_string($id) && preg_match('/^\d+$/D', $id)));
        if ($exclude) {
            [$notin, $params] = $DB->get_in_or_equal($exclude, SQL_PARAMS_NAMED, 'iex', false);
            $sql = $sql->replace_comment('exclude', new sql("AND i.id $notin", $params));
        }

        $items = $DB->get_records_sql($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($items) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($items as $item) {
            $result[(string)$item->id] = self::format_item_label($item);
        }
        return $result;
    }

    #[\Override]
    public function labels(array $values): array {
        global $DB;

        $result = [];
        foreach ($this->get_items($values) as $id => $item) {
            if ($item->status != util::STATUS_ACTIVE || $item->sectionstatus != util::STATUS_ACTIVE) {
                continue;
            }
            $params = ['collectionid' => $this->collectionid, 'itemid' => $item->id];
            if ($DB->record_exists('tool_mucatalog_collection_item', $params)) {
                continue;
            }
            if (!has_capability(self::CAPABILITY, \context::instance_by_id($item->contextid))) {
                continue;
            }
            $result[$id] = self::format_item_label($item);
        }
        return $result;
    }

    #[\Override]
    public function validate(array $values): array {
        $result = [];
        if (!mulib::is_mutenancy_active() || !$this->context->tenantid) {
            return $result;
        }
        foreach ($this->get_items($values) as $id => $item) {
            $itemcontext = \context::instance_by_id($item->contextid);
            if ($itemcontext->tenantid && $itemcontext->tenantid != $this->context->tenantid) {
                $result[$id] = get_string('error');
            }
        }
        return $result;
    }

    /**
     * Fetch items with their section details.
     *
     * @param string[] $ids
     * @return \stdClass[] indexed by string id
     */
    private function get_items(array $ids): array {
        global $DB;
        $ids = array_values(array_filter($ids, fn($id) => is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id)));
        if (!$ids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $sql = "SELECT i.id, i.name, i.type, i.status, s.name AS sectionname, s.status AS sectionstatus, s.contextid
                  FROM {tool_mucatalog_item} i
                  JOIN {tool_mucatalog_section} s ON s.id = i.sectionid
                 WHERE i.id $insql
              ORDER BY i.id ASC";
        $result = [];
        foreach ($DB->get_records_sql($sql, $params) as $item) {
            $result[(string)$item->id] = $item;
        }
        return $result;
    }

    /**
     * Item label with type and section name.
     *
     * @param \stdClass $item
     * @return string
     */
    private static function format_item_label(\stdClass $item): string {
        $context = \context::instance_by_id($item->contextid);
        $parts = [];
        $parts[] = format_string($item->name, true, ['context' => $context]);
        $classname = \tool_mucatalog\local\item::get_type_classname($item->type);
        $parts[] = $classname ? $classname::get_type_name() : get_string('error');
        $parts[] = format_string($item->sectionname, true, ['context' => $context]);
        return clean_text(implode(\moodle_page::TITLE_SEPARATOR, $parts));
    }
}
