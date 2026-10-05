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

use tool_mucatalog\local\item;
use tool_mucatalog\local\util;
use tool_mulib\local\search_util;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Sections a course, program or certification may be added to.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class reference_add_sectionid extends \tool_mulib\muform\autocomplete\base {
    /** @var string capability required in the section context */
    private const string CAPABILITY = 'tool/mucatalog:manage';

    /** @var \context context of course, program or certification */
    private \context $context;

    /**
     * Constructor.
     *
     * @param string $type item type
     * @param int $referenceid id of course, program or certification
     */
    public function __construct(
        /** @var string item type */
        private readonly string $type,
        /** @var int id of course, program or certification */
        private readonly int $referenceid
    ) {
        $typeclass = item::get_type_classname($type);
        if (!$typeclass || !$typeclass::is_available()) {
            throw new \core\exception\invalid_parameter_exception('Invalid item type');
        }
        $context = $typeclass::get_reference_context($referenceid);
        if (!$context) {
            throw new \core\exception\invalid_parameter_exception('Invalid item reference');
        }
        $this->context = $context;
        require_capability($typeclass::get_add_capability(), $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->type, $this->referenceid];
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
              LEFT JOIN {tool_mucatalog_item} ci ON ci.sectionid = s.id AND ci.type = :type AND ci.referenceid = :referenceid
                  WHERE ci.id IS NULL AND s.status <> :archived
                        /* capwhere */ /* searchsql */ /* tenantwhere */
               GROUP BY s.id, s.name, s.contextid
               ORDER BY s.name ASC, s.id ASC",
                ['type' => $this->type, 'referenceid' => $this->referenceid, 'archived' => util::STATUS_ARCHIVED]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                search_util::get_search_query(trim($query), ['name', 'shortdescription'], 's')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active() && $this->context->tenantid) {
            // Tenant sections may contain only items from the same tenant or items without tenant.
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
        global $DB;

        $section = $this->get_section($value);
        if (!$section || $section->status == util::STATUS_ARCHIVED) {
            return null;
        }
        if (!has_capability(self::CAPABILITY, \context::instance_by_id($section->contextid))) {
            return null;
        }
        // Adding one course, program or certification repeatedly to one section would be confusing.
        $params = ['sectionid' => $section->id, 'type' => $this->type, 'referenceid' => $this->referenceid];
        if ($DB->record_exists('tool_mucatalog_item', $params)) {
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
        $sectioncontext = \context::instance_by_id($section->contextid);
        if ($sectioncontext->tenantid && $sectioncontext->tenantid != $this->context->tenantid) {
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
        return $DB->get_record('tool_mucatalog_section', ['id' => $value], 'id, name, contextid, status') ?: null;
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
