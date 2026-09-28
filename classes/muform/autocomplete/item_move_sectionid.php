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

namespace tool_mucatalog\muform\autocomplete;

use tool_mulib\local\search_util;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Sections an item may be moved to.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_move_sectionid extends \tool_mulib\muform\autocomplete\base {
    /** @var string capability required in the target section context */
    private const string CAPABILITY = 'tool/mucatalog:manage';

    /** @var \stdClass current item section */
    private \stdClass $section;

    /** @var \context current section context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $itemid
     */
    public function __construct(
        /** @var int item id */
        private readonly int $itemid
    ) {
        global $DB;
        $item = $DB->get_record('tool_mucatalog_item', ['id' => $itemid], '*', MUST_EXIST);
        $this->section = $DB->get_record('tool_mucatalog_section', ['id' => $item->sectionid], '*', MUST_EXIST);
        $this->context = \context::instance_by_id($this->section->contextid);
        require_capability(self::CAPABILITY, $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->itemid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        global $DB, $USER;

        $capjoin = context_map::get_contexts_by_capability_join(self::CAPABILITY, $USER->id, 'ctx');

        $sql = (
            new sql(
                "SELECT s.id, s.name, s.contextid
                   FROM {tool_mucatalog_section} s
                   JOIN {context} ctx ON ctx.id = s.contextid
                   /* capjoin */
                  WHERE s.id <> :sectionid
                        /* capwhere */ /* searchsql */ /* tenantwhere */
               GROUP BY s.id, s.name, s.contextid
               ORDER BY s.name ASC, s.id ASC",
                ['sectionid' => $this->section->id]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                search_util::get_search_query(trim($query), ['name', 'shortdescription'], 's')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active() && $this->context->tenantid) {
            $sql = $sql->replace_comment(
                'tenantwhere',
                new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$this->context->tenantid])
            );
        }

        $sections = $DB->get_records_sql($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($sections) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($sections as $section) {
            $result[(string)$section->id] = self::format_section_name($section);
        }
        return $result;
    }

    #[\Override]
    public function label(string $value): ?string {
        $section = $this->get_section($value);
        if (!$section || $section->id == $this->section->id) {
            return null;
        }
        if (!has_capability(self::CAPABILITY, \context::instance_by_id($section->contextid))) {
            return null;
        }
        return self::format_section_name($section);
    }

    #[\Override]
    public function validate(string $value): ?string {
        if (!mulib::is_mutenancy_active() || !$this->context->tenantid) {
            return null;
        }
        $section = $this->get_section($value);
        if (!$section) {
            return null;
        }
        $newcontext = \context::instance_by_id($section->contextid);
        if ($newcontext->tenantid && $newcontext->tenantid != $this->context->tenantid) {
            return get_string('error');
        }
        return null;
    }

    /**
     * Fetch section.
     *
     * @param string $value
     * @return \stdClass|null
     */
    private function get_section(string $value): ?\stdClass {
        global $DB;
        if (!preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }
        return $DB->get_record('tool_mucatalog_section', ['id' => $value], 'id, name, contextid') ?: null;
    }

    /**
     * Section name.
     *
     * @param \stdClass $section
     * @return string
     */
    private static function format_section_name(\stdClass $section): string {
        return clean_text(format_string($section->name, true, ['context' => \context::instance_by_id($section->contextid)]));
    }
}
