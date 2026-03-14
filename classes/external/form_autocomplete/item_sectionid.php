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

/**
 * Move item to different section candidates.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_sectionid extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null course table */
    protected const ITEM_TABLE = 'tool_mucatalog_section';
    /** @var string|null field used for item name */
    protected const ITEM_FIELD = 'name';

    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'itemid' => new external_value(PARAM_INT, 'Item id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds candidate sections for moving of item.
     *
     * @param string $query The search request.
     * @param int $itemid The item.
     * @return array
     */
    public static function execute(string $query, int $itemid): array {
        global $DB, $USER;

        [
            'query' => $query,
            'itemid' => $itemid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'itemid' => $itemid,
        ]);

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $itemid], '*', MUST_EXIST);
        $section = $DB->get_record('tool_mucatalog_section', ['id' => $item->sectionid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($section->contextid);
        self::validate_context($context);
        require_capability('tool/mucatalog:manage', $context);

        $capjoin = context_map::get_contexts_by_capability_join('tool/mucatalog:manage', $USER->id, 'ctx');

        $sql = (
            new sql(
                "SELECT s.id, s.name
                   FROM {tool_mucatalog_section} s
                   JOIN {context} ctx ON ctx.id = s.contextid
                   /* capjoin */
                  WHERE s.id <> :sectionid
                        /* capwhere */ /* searchsql */ /* tenantwhere */
               GROUP BY s.id, s.name
               ORDER BY s.name ASC",
                ['sectionid' => $section->id]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                self::get_search_query($query, ['name', 'shortdescription'], 'c')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantwhere',
                    new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$context->tenantid])
                );
            }
        }

        $courses = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($courses, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $newsection = $DB->get_record('tool_mucatalog_section', ['id' => $value]);
        if (!$newsection) {
            return get_string('error');
        }
        $newcontext = \context::instance_by_id($newsection->contextid);

        if (!has_capability('tool/mucatalog:manage', $newcontext)) {
            return get_string('error');
        }

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                if ($newcontext->tenantid && $newcontext->tenantid != $context->tenantid) {
                    return get_string('error');
                }
            }
        }

        return null;
    }
}
