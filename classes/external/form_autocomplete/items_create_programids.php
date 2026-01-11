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
 * Add program to catalogue.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class items_create_programids extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null program table */
    protected const ITEM_TABLE = 'tool_muprog_program';
    /** @var string|null field used for item name */
    protected const ITEM_FIELD = 'fullname';

    #[\Override]
    public static function get_multiple(): bool {
        return true;
    }

    #[\Override]
    public static function get_form_field_name(): string {
        return get_string('programs', 'tool_muprog');
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'sectionid' => new external_value(PARAM_INT, 'Section id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds candidates for adding programs to section.
     *
     * @param string $query The search request.
     * @param int $sectionid The section.
     * @return array
     */
    public static function execute(string $query, int $sectionid): array {
        global $DB, $USER;

        [
            'query' => $query,
            'sectionid' => $sectionid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'sectionid' => $sectionid,
        ]);

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($section->contextid);
        self::validate_context($context);
        require_capability('tool/mucatalog:managesections', $context);

        $capjoin = context_map::get_contexts_by_capability_join('tool/mucatalog:addprogram', $USER->id, 'ctx');

        // NOTE: for now only manual programs are returned.

        $sql = (
            new sql(
                "SELECT p.id, p.fullname
                   FROM {tool_muprog_program} p
                   JOIN {context} ctx ON ctx.id = p.contextid
                   /* capjoin */
              LEFT JOIN {tool_mucatalog_item} ci ON ci.sectionid = :sectionid AND ci.type = 'program' AND ci.referenceid = p.id
                  WHERE ci.id IS NULL
                        /* capwhere */ /* searchsql */ /* tenantwhere */
               GROUP BY p.id, p.fullname
               ORDER BY p.fullname ASC",
                ['sectionid' => $section->id]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                self::get_search_query($query, ['fullname', 'idnumber'], 'c')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantwhere',
                    new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$context->tenantid])
                );
            }
        }

        $programs = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($programs, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $value]);
        if (!$program) {
            return get_string('error');
        }

        $sectionid = $args['sectionid'];
        $section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionid], '*', MUST_EXIST);

        // NOTE: technically we could allow one program to be included repeatedly in one section, but that would be confusing.
        if ($DB->record_exists('tool_mucatalog_item', ['sectionid' => $section->id, 'type' => 'program', 'referenceid' => $program->id])) {
            return get_string('error');
        }

        $programcontext = \context::instance_by_id($program->contextid);
        if (!has_capability('tool/mucatalog:addprogram', $programcontext)) {
            return get_string('error');
        }

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                if ($programcontext->tenantid && $programcontext->tenantid != $context->tenantid) {
                    return get_string('error');
                }
            }
        }

        return null;
    }
}
