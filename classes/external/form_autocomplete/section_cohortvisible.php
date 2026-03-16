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
 * Section add/update visible to cohortids autocompletion.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class section_cohortvisible extends \tool_mulib\external\form_autocomplete\cohort {
    #[\Override]
    public static function get_multiple(): bool {
        return true;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'sectionid' => new external_value(PARAM_INT, 'Section id', VALUE_REQUIRED),
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available cohorts.
     *
     * @param string $query The search request.
     * @param int|null $sectionid if empty contextid must be provided
     * @param int|null $contextid ignored if sectionid provided
     * @return array
     */
    public static function execute(string $query, ?int $sectionid, ?int $contextid): array {
        global $DB, $USER;

        [
            'query' => $query,
            'sectionid' => $sectionid,
            'contextid' => $contextid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'sectionid' => $sectionid,
            'contextid' => $contextid,
        ]);

        if ($sectionid) {
            $section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionid], '*', MUST_EXIST);
            $context = \context::instance_by_id($section->contextid);
        } else {
            $context = \context::instance_by_id($contextid);
        }
        self::validate_context($context);
        require_capability('tool/mucatalog:manage', $context);

        $sql = (new sql(
            "SELECT ch.id, ch.name
               FROM {cohort} ch
               /* tenantjoin */
               /* capsubquery */
             /* capwhere */ /* searchsql */
           ORDER BY ch.name ASC"
        ))
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    'moodle/cohort:view',
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [\context_system::LEVEL, \context_coursecat::LEVEL])
                )->wrap("LEFT JOIN (", ")capctx ON capctx.id = ch.contextid")
            )
            ->replace_comment(
                'capwhere',
                "WHERE (ch.visible = 1 OR capctx.id IS NOT NULL)"
            )
            ->replace_comment(
                'searchsql',
                self::get_cohort_search_query($query, 'ch')->wrap('AND ', '')
            );

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantjoin',
                    new sql("JOIN {context} tctx ON tctx.id = ch.contextid AND (tctx.tenantid = ? OR tctx.tenantid IS NULL)", [$context->tenantid])
                );
            }
        }

        $cohorts = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($cohorts, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;
        $cohort = $DB->get_record('cohort', ['id' => $value]);
        if (!$cohort) {
            return get_string('error');
        }

        if (!empty($args['sectionid'])) {
            $section = $DB->get_record('tool_mucatalog_section', ['id' => $args['sectionid']], '*', MUST_EXIST);
            if ($section->contextid != $context->id) {
                debugging('section contextid parameter mismatch', DEBUG_DEVELOPER);
                return get_string('error');
            }
            if ($DB->record_exists('tool_mucatalog_section_cohortvisible', ['cohortid' => $cohort->id, 'sectionid' => $section->id])) {
                // Existing cohorts are always fine.
                return null;
            }
        } else {
            if ($args['contextid'] != $context->id) {
                debugging('contextid parameter mismatch', DEBUG_DEVELOPER);
                return get_string('error');
            }
        }

        $cohortcontext = \context::instance_by_id($cohort->contextid, IGNORE_MISSING);
        if (!$cohortcontext) {
            return get_string('error');
        }

        if (mulib::is_mutenancy_active()) {
            if ($cohortcontext->tenantid && $context->tenantid && $cohortcontext->tenantid != $context->tenantid) {
                // Do not allow cohorts from other tenants.
                return get_string('error');
            }
        }
        if (!self::is_cohort_visible($cohort)) {
            return get_string('error');
        }
        return null;
    }
}
