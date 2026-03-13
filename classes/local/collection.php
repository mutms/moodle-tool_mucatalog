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

namespace tool_mucatalog\local;

use stdClass;
use tool_mulib\local\sql;
use core\exception\invalid_parameter_exception;

/**
 * Universal catalogue collection.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection {
    /**
     * Returns defaults for new collection.
     *
     * @param int|null $contextid
     * @return stdClass
     */
    public static function get_defaults(?int $contextid): stdClass {
        $collection = new stdClass();
        if (isset($contextid)) {
            $collection->contextid = (string)$contextid;
        }
        $collection->frontpagepriority = null;
        $collection->guestvisible = '0';
        $collection->uservisible = '1';

        return $collection;
    }

    /**
     * Create collection.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $record = new stdClass();

        if (empty($data->contextid)) {
            throw new invalid_parameter_exception('collection contextid is required');
        }
        $context = \context::instance_by_id($data->contextid);
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            throw new invalid_parameter_exception('System or category context expected');
        }
        $record->contextid = $context->id;

        if (trim($data->name ?? '') === '') {
            throw new invalid_parameter_exception('collection name is required');
        }
        $record->name = $data->name;
        if (\core_text::strlen($data->name) > 254) {
            $record->name = \core_text::substr($record->name, 254);
        }

        $record->shortdescription = $data->shortdescription ?? '';

        if (isset($data->frontpageshow) && !$data->frontpageshow) {
            $data->frontpagepriority = null;
        } else {
            $record->frontpagepriority = $data->frontpagepriority ?? null;
            if (!$record->frontpagepriority || trim($record->frontpagepriority) === '') {
                $record->frontpagepriority = null;
            } else {
                $record->frontpagepriority = (int)$record->frontpagepriority;
            }
        }

        $record->guestvisible = (int)(bool)($data->guestvisible ?? 0);
        $record->uservisible = (int)(bool)($data->uservisible ?? 0);

        if (\tool_mulib\local\mulib::is_mutenancy_active() && !$context->tenantid) {
            $record->hiddenfromtenants = (int)(bool)($data->hiddenfromtenants ?? 0);
        } else {
            $record->hiddenfromtenants = 0;
        }

        $record->timecreated = time();
        $record->timemodified = $record->timecreated;

        $record->presentationjson = '{}';

        $trans = $DB->start_delegated_transaction();

        $record->id = $DB->insert_record('tool_mucatalog_collection', $record);

        if (!$record->uservisible && !empty($data->cohortvisible)) {
            foreach ($data->cohortvisible as $cohortid) {
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                $DB->insert_record('tool_mucatalog_collection_cohortvisible', ['collectionid' => $record->id, 'cohortid' => $cohort->id]);
            }
        }

        $trans->allow_commit();

        return $DB->get_record('tool_mucatalog_collection', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Update collection.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $record = new stdClass();
        $record->id = $data->id;

        $oldcollection = $DB->get_record('tool_mucatalog_collection', ['id' => $record->id], '*', MUST_EXIST);
        $context = \context::instance_by_id($oldcollection->contextid);

        if (property_exists($data, 'name')) {
            if (trim($data->name) === '') {
                throw new invalid_parameter_exception('collection name is required');
            }
            $record->name = $data->name;
            if (\core_text::strlen($data->name) > 254) {
                $record->name = \core_text::substr($record->name, 254);
            }
        }

        if (property_exists($data, 'shortdescription')) {
            $record->shortdescription = $data->shortdescription ?? '';
        }

        if (isset($data->frontpageshow) && !$data->frontpageshow) {
            $data->frontpagepriority = null;
        } else {
            if (property_exists($data, 'frontpagepriority')) {
                $record->frontpagepriority = $data->frontpagepriority;
                if (!$record->frontpagepriority || trim($record->frontpagepriority) === '') {
                    $record->frontpagepriority = null;
                } else {
                    $record->frontpagepriority = (int)$record->frontpagepriority;
                }
            }
        }

        if (property_exists($data, 'guestvisible')) {
            $record->guestvisible = (int)(bool)($data->guestvisible ?? 0);
        }
        if (property_exists($data, 'uservisible')) {
            $record->uservisible = (int)(bool)($data->uservisible ?? 0);
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active() && !$context->tenantid) {
            if (property_exists($data, 'hiddenfromtenants')) {
                $record->hiddenfromtenants = (int)(bool)$data->hiddenfromtenants;
            }
        } else {
            $record->hiddenfromtenants = 0;
        }

        $record->timemodified = time();

        $trans = $DB->start_delegated_transaction();

        $DB->update_record('tool_mucatalog_collection', $record);
        $record = $DB->get_record('tool_mucatalog_collection', ['id' => $record->id], '*', MUST_EXIST);

        if ($record->uservisible) {
            $DB->delete_records('tool_mucatalog_collection_cohortvisible', ['collectionid' => $record->id]);
        } else if (property_exists($data, 'cohortvisible')) {
            $currentcohorts = self::get_cohortvisible_menu($oldcollection->id);
            foreach ($data->cohortvisible as $cohortid) {
                if (isset($currentcohorts[$cohortid])) {
                    unset($currentcohorts[$cohortid]);
                    continue;
                }
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                $DB->insert_record('tool_mucatalog_collection_cohortvisible', ['collectionid' => $record->id, 'cohortid' => $cohort->id]);
            }
            foreach ($currentcohorts as $cohortid => $unused) {
                $DB->delete_records('tool_mucatalog_collection_cohortvisible', ['collectionid' => $record->id, 'cohortid' => $cohortid]);
            }
        }

        $trans->allow_commit();

        return $record;
    }

    /**
     * Move collection to a different context.
     *
     * @param int $id
     * @param int $contextid
     * @return stdClass
     */
    public static function move(int $id, int $contextid): stdClass {
        global $DB;

        $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $id], '*', MUST_EXIST);
        if ($collection->contextid == $contextid) {
            return $collection;
        }

        $context = \context::instance_by_id($contextid);
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            throw new invalid_parameter_exception('System or category context expected');
        }

        // No need to move collection files because they are always stored in the system context.

        $record = [
            'id' => $collection->id,
            'contextid' => $context->id,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_collection', $record);

        return $DB->get_record('tool_mucatalog_collection', ['id' => $collection->id], '*', MUST_EXIST);
    }

    /**
     * Delete collection.
     *
     * @param int $id
     * @return void
     */
    public static function delete(int $id): void {
        global $DB;

        $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $id]);
        if (!$collection) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('tool_mucatalog_collection_cohortvisible', ['collectionid' => $collection->id]);

        $DB->delete_records('tool_mucatalog_collection_item', ['collectionid' => $collection->id]);

        $DB->delete_records('tool_mucatalog_collection', ['id' => $collection->id]);

        $trans->allow_commit();
    }

    /**
     * Fetches cohorts collection is visible to.
     *
     * @param int $collectionid
     * @return array non-formated menu of visible cohorts
     */
    public static function get_cohortvisible_menu(int $collectionid): array {
        global $DB;

        $sql = new sql(
            "SELECT c.id, c.name
               FROM {cohort} c
               JOIN {tool_mucatalog_collection_cohortvisible} vc ON c.id = vc.cohortid
              WHERE vc.collectionid = ?
           ORDER BY c.name ASC, c.id ASC",
            [$collectionid]
        );

        return $DB->get_records_sql_menu($sql->sql, $sql->params);
    }

    /**
     * Add item to collection.
     *
     * @param int $collectionid
     * @param int $itemid
     * @return stdClass
     */
    public static function add_item(int $collectionid, int $itemid): stdClass {
        global $DB;

        $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $collectionid], '*', MUST_EXIST);
        $item = $DB->get_record('tool_mucatalog_item', ['id' => $itemid], '*', MUST_EXIST);

        if ($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection->id, 'itemid' => $item->id])) {
            throw new invalid_parameter_exception('items cannot be duplicated in collections');
        }

        $record = (object)[
            'collectionid' => $collection->id,
            'itemid' => $item->id,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record('tool_mucatalog_collection_item', $record);

        return $DB->get_record('tool_mucatalog_collection_item', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Remove item from collection.
     *
     * @param int $collectionid
     * @param int $itemid
     * @return void
     */
    public static function remove_item(int $collectionid, int $itemid): void {
        global $DB;

        $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $collectionid], '*', MUST_EXIST);
        $item = $DB->get_record('tool_mucatalog_item', ['id' => $itemid], '*', MUST_EXIST);

        $DB->delete_records('tool_mucatalog_collection_item', ['collectionid' => $collection->id, 'itemid' => $item->id]);
    }

    /**
     * When deleting course category delete all draft collections attached to that category,
     * archived active collections and finally move archived collections to system context.
     *
     * @param int $categoryid
     * @return void
     */
    public static function pre_course_category_delete(int $categoryid): void {
        global $DB;

        $syscontext = \context_system::instance();
        $catcontext = \context_coursecat::instance($categoryid);

        $collections = $DB->get_records('tool_mucatalog_collection', ['contextid' => $catcontext->id]);
        foreach ($collections as $collection) {
            self::move($collection->id, $syscontext->id);
        }
    }
}
