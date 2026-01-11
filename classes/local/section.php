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
 * Universal catalogue section.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class section {
    /**
     * Returns defaults for new section.
     *
     * @param int|null $contextid
     * @return stdClass
     */
    public static function get_defaults(?int $contextid): stdClass {
        $section = new stdClass();
        if (isset($contextid)) {
            $section->contextid = (string)$contextid;
        }
        $section->frontpagepriority = null;
        $section->guestvisible = '0';
        $section->uservisible = '1';
        $section->status = (string)util::STATUS_ACTIVE;

        return $section;
    }

    /**
     * Create section.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $record = new stdClass();

        if (empty($data->contextid)) {
            throw new invalid_parameter_exception('section contextid is required');
        }
        $context = \context::instance_by_id($data->contextid);
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            throw new invalid_parameter_exception('System or category context expected');
        }
        $record->contextid = $context->id;

        if (trim($data->name ?? '') === '') {
            throw new invalid_parameter_exception('section name is required');
        }
        $record->name = $data->name;
        if (\core_text::strlen($data->name) > 254) {
            $record->name = \core_text::substr($record->name, 254);
        }

        $record->shortdescription = $data->shortdescription ?? '';

        if (isset($data->frontpageshow) && !$data->frontpageshow) {
            $data->frontpagepriority = null;
        }
        $record->frontpagepriority = $data->frontpagepriority ?? null;
        if (!$record->frontpagepriority || trim($record->frontpagepriority) === '') {
            $record->frontpagepriority = null;
        } else {
            $record->frontpagepriority = (int)$record->frontpagepriority;
        }

        $record->status = (int)($data->status ?? util::STATUS_DRAFT);
        $options = util::get_statuses_menu();
        if (!isset($options[$record->status])) {
            throw new invalid_parameter_exception('invalid section status');
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

        $record->id = $DB->insert_record('tool_mucatalog_section', $record);

        if (!$record->uservisible && !empty($data->cohortvisible)) {
            foreach ($data->cohortvisible as $cohortid) {
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                $DB->insert_record('tool_mucatalog_section_cohortvisible', ['sectionid' => $record->id, 'cohortid' => $cohort->id]);
            }
        }

        $trans->allow_commit();
        self::fix_mucatalog_active();

        return $DB->get_record('tool_mucatalog_section', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Update section.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $record = new stdClass();
        $record->id = $data->id;

        $oldsection = $DB->get_record('tool_mucatalog_section', ['id' => $record->id], '*', MUST_EXIST);
        $context = \context::instance_by_id($oldsection->contextid);

        if (property_exists($data, 'name')) {
            if (trim($data->name) === '') {
                throw new invalid_parameter_exception('section name is required');
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
        }
        if (property_exists($data, 'frontpagepriority')) {
            $record->frontpagepriority = $data->frontpagepriority;
            if (!$record->frontpagepriority || trim($record->frontpagepriority) === '') {
                $record->frontpagepriority = null;
            } else {
                $record->frontpagepriority = (int)$record->frontpagepriority;
            }
        }

        if ($oldsection->status == util::STATUS_DRAFT) {
            if (property_exists($data, 'status')) {
                $options = util::get_statuses_menu();
                unset($options[util::STATUS_ARCHIVED]);
                if (!isset($options[$data->status])) {
                    throw new invalid_parameter_exception('invalid section status');
                }
                $record->status = $data->status;
            }
        } else {
            if (property_exists($data, 'status') && $oldsection->status != $data->status) {
                throw new invalid_parameter_exception('non-draft section status cannot be changed, use archive or restore method');
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

        $DB->update_record('tool_mucatalog_section', $record);
        $record = $DB->get_record('tool_mucatalog_section', ['id' => $record->id], '*', MUST_EXIST);

        if ($record->uservisible) {
            $DB->delete_records('tool_mucatalog_section_cohortvisible', ['sectionid' => $record->id]);
        } else if (property_exists($data, 'cohortvisible')) {
            $currentcohorts = self::get_cohortvisible_menu($oldsection->id);
            foreach ($data->cohortvisible as $cohortid) {
                if (isset($currentcohorts[$cohortid])) {
                    unset($currentcohorts[$cohortid]);
                    continue;
                }
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                $DB->insert_record('tool_mucatalog_section_cohortvisible', ['sectionid' => $record->id, 'cohortid' => $cohort->id]);
            }
            foreach ($currentcohorts as $cohortid => $unused) {
                $DB->delete_records('tool_mucatalog_section_cohortvisible', ['sectionid' => $record->id, 'cohortid' => $cohortid]);
            }
        }

        $trans->allow_commit();
        self::fix_mucatalog_active();

        return $record;
    }

    /**
     * Move section to a different context.
     *
     * @param int $id
     * @param int $contextid
     * @return stdClass
     */
    public static function move(int $id, int $contextid): stdClass {
        global $DB;

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $id], '*', MUST_EXIST);
        if ($section->contextid == $contextid) {
            return $section;
        }

        $context = \context::instance_by_id($contextid);
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            throw new invalid_parameter_exception('System or category context expected');
        }

        // No need to move section or item files because they are always stored in the system context.

        $record = [
            'id' => $section->id,
            'contextid' => $context->id,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_section', $record);
        self::fix_mucatalog_active();

        return $DB->get_record('tool_mucatalog_section', ['id' => $section->id], '*', MUST_EXIST);
    }

    /**
     * Is section activation possible?
     *
     * @param stdClass $section
     * @return bool
     */
    public static function is_activate_possible(stdClass $section): bool {
        return ($section->status == util::STATUS_DRAFT);
    }

    /**
     * Activate draft section.
     *
     * @param int $id
     * @return stdClass section record
     */
    public static function activate(int $id): stdClass {
        global $DB;

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $id], '*', MUST_EXIST);

        if ($section->status == util::STATUS_ACTIVE) {
            return $section;
        }

        if ($section->status != util::STATUS_DRAFT) {
            throw new invalid_parameter_exception('only draft sections can be activated');
        }

        $record = [
            'id' => $section->id,
            'status' => util::STATUS_ACTIVE,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_section', $record);
        self::fix_mucatalog_active();

        return $DB->get_record('tool_mucatalog_section', ['id' => $section->id], '*', MUST_EXIST);
    }

    /**
     * Is section archiving possible?
     *
     * @param stdClass $section
     * @return bool
     */
    public static function is_archive_possible(stdClass $section): bool {
        return ($section->status == util::STATUS_ACTIVE);
    }

    /**
     * Archive section.
     *
     * @param int $id
     * @return stdClass section record
     */
    public static function archive(int $id): stdClass {
        global $DB;

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $id], '*', MUST_EXIST);

        if ($section->status == util::STATUS_ARCHIVED) {
            return $section;
        }

        if ($section->status != util::STATUS_ACTIVE) {
            throw new invalid_parameter_exception('only active sections can be archived');
        }

        $record = [
            'id' => $section->id,
            'status' => util::STATUS_ARCHIVED,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_section', $record);
        self::fix_mucatalog_active();

        return $DB->get_record('tool_mucatalog_section', ['id' => $section->id], '*', MUST_EXIST);
    }

    /**
     * Is section restoring possible?
     *
     * @param stdClass $section
     * @return bool
     */
    public static function is_restore_possible(stdClass $section): bool {
        return ($section->status == util::STATUS_ARCHIVED);
    }

    /**
     * Restore section.
     *
     * @param int $id
     * @return stdClass section record
     */
    public static function restore(int $id): stdClass {
        global $DB;

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $id], '*', MUST_EXIST);

        if ($section->status == util::STATUS_ACTIVE) {
            return $section;
        }

        if ($section->status != util::STATUS_ARCHIVED) {
            throw new invalid_parameter_exception('only archived sections can be restored');
        }

        $record = [
            'id' => $section->id,
            'status' => util::STATUS_ACTIVE,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_section', $record);
        self::fix_mucatalog_active();

        return $DB->get_record('tool_mucatalog_section', ['id' => $section->id], '*', MUST_EXIST);
    }

    /**
     * Is section deleting possible?
     *
     * @param stdClass $section
     * @return bool
     */
    public static function is_delete_possible(stdClass $section): bool {
        return ($section->status != util::STATUS_ACTIVE);
    }

    /**
     * Delete section.
     *
     * @param int $id
     * @return void
     */
    public static function delete(int $id): void {
        global $DB;

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $id]);
        if (!$section) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $items = $DB->get_records('tool_mucatalog_item', ['sectionid' => $section->id]);
        foreach ($items as $item) {
            $typeclass = item::get_type_classname($item->type);
            $typeclass::delete($item->id);
        }

        $DB->delete_records('tool_mucatalog_section_cohortvisible', ['sectionid' => $section->id]);

        $DB->delete_records('tool_mucatalog_section', ['id' => $section->id]);

        $trans->allow_commit();
        self::fix_mucatalog_active();
    }

    /**
     * Fetches cohorts section is visible to.
     *
     * @param int $sectionid
     * @return array non-formated menu of visible cohorts
     */
    public static function get_cohortvisible_menu(int $sectionid): array {
        global $DB;

        $sql = new sql(
            "SELECT c.id, c.name
               FROM {cohort} c
               JOIN {tool_mucatalog_section_cohortvisible} vc ON c.id = vc.cohortid
              WHERE vc.sectionid = ?
           ORDER BY c.name ASC, c.id ASC",
            [$sectionid]
        );

        return $DB->get_records_sql_menu($sql->sql, $sql->params);
    }

    /**
     * Cache existence of active sections and guest section visibility.
     */
    public static function fix_mucatalog_active(): void {
        global $DB;

        $active = (int)$DB->record_exists('tool_mucatalog_section', ['status' => util::STATUS_ACTIVE]);
        set_config('active', $active, 'tool_mucatalog');

        if ($active) {
            $guestvisible = (int)$DB->record_exists('tool_mucatalog_section', ['status' => util::STATUS_ACTIVE, 'guestvisible' => 1]);
            set_config('guestvisible', $guestvisible, 'tool_mucatalog');
        } else {
            set_config('guestvisible', $active, 'tool_mucatalog');
        }
    }

    /**
     * When deleting course category delete all draft sections attached to that category,
     * archived active sections and finally move archived sections to system context.
     *
     * @param int $categoryid
     * @return void
     */
    public static function pre_course_category_delete(int $categoryid): void {
        global $DB;

        $syscontext = \context_system::instance();
        $catcontext = \context_coursecat::instance($categoryid);

        $sections = $DB->get_records('tool_mucatalog_section', ['contextid' => $catcontext->id]);
        foreach ($sections as $section) {
            if ($section->status == util::STATUS_DRAFT) {
                self::delete($section->id);
                continue;
            }
            if ($section->status == util::STATUS_ACTIVE) {
                $section = self::archive($section->id);
            }
            self::move($section->id, $syscontext->id);
        }
    }

    /**
     * Event callback.
     *
     * @param \core\event\cohort_deleted $event
     * @return void
     */
    public static function event_cohort_deleted(\core\event\cohort_deleted $event): void {
        global $DB;

        $DB->delete_records('tool_mucatalog_section_cohortvisible', ['cohortid' => $event->objectid]);
        $DB->delete_records('tool_mucatalog_collection_cohortvisible', ['cohortid' => $event->objectid]);
    }
}
