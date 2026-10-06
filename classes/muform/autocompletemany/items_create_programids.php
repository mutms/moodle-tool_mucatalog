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

use tool_mulib\local\search_util;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Programs that may be added to a catalogue section.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class items_create_programids extends \tool_mulib\muform\autocompletemany\base {
    /** @var string capability required in the program context */
    private const string CAPABILITY = 'tool/mucatalog:addprogram';

    /** @var \context section context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $sectionid
     */
    public function __construct(
        /** @var int section id */
        private readonly int $sectionid
    ) {
        global $DB;
        $section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionid], '*', MUST_EXIST);
        $this->context = \context::instance_by_id($section->contextid);
        require_capability('tool/mucatalog:manage', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->sectionid];
    }

    #[\Override]
    public function search(string $query, int $maxitems, array $exclude): ?array {
        global $DB, $USER;

        $capjoin = context_map::get_contexts_by_capability_join(self::CAPABILITY, $USER->id, 'ctx');

        $sql = (
            new sql(
                "SELECT p.id, p.fullname, p.contextid
                   FROM {tool_muprog_program} p
                   JOIN {context} ctx ON ctx.id = p.contextid
                   /* capjoin */
              LEFT JOIN {tool_mucatalog_item} ci ON ci.sectionid = :sectionid AND ci.type = 'program' AND ci.referenceid = p.id
                  WHERE ci.id IS NULL AND p.archived = 0 AND p.draft = 0
                        /* capwhere */ /* searchsql */ /* tenantwhere */ /* exclude */
               GROUP BY p.id, p.fullname, p.contextid
               ORDER BY p.fullname ASC, p.id ASC",
                ['sectionid' => $this->sectionid]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                search_util::get_search_query(trim($query), ['fullname', 'idnumber'], 'p')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active() && $this->context->tenantid) {
            $sql = $sql->replace_comment(
                'tenantwhere',
                new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$this->context->tenantid])
            );
        }

        $exclude = array_values(array_filter($exclude, fn($id) => is_string($id) && preg_match('/^\d+$/D', $id)));
        if ($exclude) {
            [$notin, $params] = $DB->get_in_or_equal($exclude, SQL_PARAMS_NAMED, 'pex', false);
            $sql = $sql->replace_comment('exclude', new sql("AND p.id $notin", $params));
        }

        $programs = $DB->get_records_sql($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($programs) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($programs as $program) {
            $programcontext = \context::instance_by_id($program->contextid);
            $result[(string)$program->id] = clean_text(format_string($program->fullname, true, ['context' => $programcontext]));
        }
        return $result;
    }

    #[\Override]
    public function labels(array $values): array {
        global $DB;

        $result = [];
        foreach ($this->get_programs($values) as $id => $program) {
            if ($program->archived || $program->draft) {
                continue;
            }
            // Adding one program repeatedly to one section would be confusing.
            $params = ['sectionid' => $this->sectionid, 'type' => 'program', 'referenceid' => $program->id];
            if ($DB->record_exists('tool_mucatalog_item', $params)) {
                continue;
            }
            $programcontext = \context::instance_by_id($program->contextid);
            if (!has_capability(self::CAPABILITY, $programcontext)) {
                continue;
            }
            $result[$id] = clean_text(format_string($program->fullname, true, ['context' => $programcontext]));
        }
        return $result;
    }

    #[\Override]
    public function validate(array $values): array {
        $result = [];
        if (!mulib::is_mutenancy_active() || !$this->context->tenantid) {
            return $result;
        }
        foreach ($this->get_programs($values) as $id => $program) {
            $programcontext = \context::instance_by_id($program->contextid);
            if ($programcontext->tenantid && $programcontext->tenantid != $this->context->tenantid) {
                $result[$id] = get_string('error');
            }
        }
        return $result;
    }

    /**
     * Fetch programs.
     *
     * @param string[] $ids
     * @return \stdClass[] indexed by string id
     */
    private function get_programs(array $ids): array {
        global $DB;
        $ids = array_values(array_filter($ids, fn($id) => is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id)));
        if (!$ids) {
            return [];
        }
        $result = [];
        $programs = $DB->get_records_list('tool_muprog_program', 'id', $ids, 'id ASC', 'id, contextid, fullname, archived, draft');
        foreach ($programs as $program) {
            $result[(string)$program->id] = $program;
        }
        return $result;
    }
}
