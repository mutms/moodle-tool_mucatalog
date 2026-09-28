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

use tool_mulib\muform\util\autocomplete\cohort_trait;

/**
 * Cohorts that may see a collection.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection_cohortvisible extends \tool_mulib\muform\autocompletemany\base {
    use cohort_trait;

    /** @var \context collection context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $collectionid existing collection, 0 for a new one
     * @param int $contextid context of a new collection, ignored for existing collections
     */
    public function __construct(
        /** @var int collection id */
        private readonly int $collectionid,
        /** @var int context id of new collection */
        private readonly int $contextid
    ) {
        global $DB;
        if ($collectionid) {
            $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $collectionid], '*', MUST_EXIST);
            $this->context = \context::instance_by_id($collection->contextid);
        } else {
            $this->context = \context::instance_by_id($contextid);
        }
        require_capability('tool/mucatalog:manage', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->collectionid, $this->contextid];
    }

    #[\Override]
    public function search(string $query, int $maxitems, array $exclude): ?array {
        return $this->search_cohorts($this->context, $query, $maxitems, $exclude);
    }

    #[\Override]
    public function labels(array $values): array {
        return $this->cohort_labels($this->context, $values, $this->get_current());
    }

    #[\Override]
    public function validate(array $values): array {
        return $this->validate_cohorts($this->context, $values, $this->get_current());
    }

    /**
     * Cohorts already used.
     *
     * @return int[]
     */
    private function get_current(): array {
        global $DB;
        if (!$this->collectionid) {
            return [];
        }
        $params = ['collectionid' => $this->collectionid];
        $cohortids = $DB->get_fieldset('tool_mucatalog_collection_cohortvisible', 'cohortid', $params);
        return array_map('intval', $cohortids);
    }
}
