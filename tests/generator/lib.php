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

use tool_mucatalog\local\section;
use tool_mucatalog\local\collection;
use tool_mucatalog\local\item;
use tool_mucatalog\local\util;

/**
 * Catalogue test data generator.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_mucatalog_generator extends component_generator_base {
    /** @var int section count */
    private $sectioncount = 0;
    /** @var int collection count */
    private $collectioncount = 0;

    #[\Override]
    public function reset(): void {
        $this->sectioncount = 0;
        $this->collectioncount = 0;
    }

    /**
     * Create a new section.
     *
     * @param stdClass|array|null $record
     * @return stdClass section record
     */
    public function create_section($record = null): stdClass {
        $this->sectioncount++;

        $syscontext = context_system::instance();

        $record = (array)$record;
        $defaults = (array)section::get_defaults($record['contextid'] ?? $syscontext->id);
        $record = (object)array_merge($defaults, $record);

        if (!isset($record->name)) {
            $record->name = 'Section ' . $this->sectioncount;
        }
        if (trim($record->shortdescription ?? '') === '') {
            $record->shortdescription = 'Description of section ' . $record->name;
        }

        return section::create($record);
    }

    /**
     * Create a new item for existing reference.
     *
     * @param stdClass|array $record
     * @return stdClass tool_mucatalog_item record
     */
    public function create_item($record): stdClass {
        $record = (object)(array)$record;

        $typeclass = item::get_type_classname($record->type);
        if (!$typeclass || !$typeclass::is_available()) {
            throw new \core\exception\coding_exception("Item type '$record->type' is not available");
        }
        if (!isset($record->status)) {
            $record->status = util::STATUS_ACTIVE;
        }
        if (trim($record->name ?? '') != '') {
            if (!isset($record->syncname) || $record->syncname === '') {
                $record->syncname = '0';
            }
        }

        return $typeclass::create($record);
    }

    /**
     * Create a new collection.
     *
     * @param stdClass|array|null $record
     * @return stdClass collection record
     */
    public function create_collection($record = null): stdClass {
        $this->collectioncount++;

        $syscontext = context_system::instance();

        $record = (array)$record;
        $defaults = (array)collection::get_defaults($record['contextid'] ?? $syscontext->id);
        $record = (object)array_merge($defaults, $record);

        if (!isset($record->name)) {
            $record->name = 'Collection ' . $this->collectioncount;
        }
        if (trim($record->shortdescription ?? '') === '') {
            $record->shortdescription = 'Description of collection ' . $record->name;
        }

        return collection::create($record);
    }

    /**
     * Add item to collection.
     *
     * @param stdClass|array $record
     * @return stdClass tool_mucatalog_collection_item record
     */
    public function create_collection_item($record): stdClass {
        $record = (object)(array)$record;
        return collection::add_item($record->collectionid, $record->itemid);
    }
}
