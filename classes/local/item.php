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

use core\exception\coding_exception;
use core\exception\invalid_parameter_exception;
use stdClass;
use core\url;
/**
 * Base class for section items.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class item {
    /** @var string item type, must be overridden */
    public const TYPE = null;

    /**
     * Returns list of available item type classes.
     *
     * @return class-string<\tool_mucatalog\local\item>[]
     */
    final public static function get_type_classnames(): array {
        return [
            item\course::get_type() => item\course::class,
            item\program::get_type() => item\program::class,
            item\certification::get_type() => item\certification::class,
        ];
    }

    /**
     * Returns list of available item type classes.
     *
     * @param string $type
     * @return null|class-string<\tool_mucatalog\local\item>
     */
    final public static function get_type_classname(string $type): ?string {
        $types = self::get_type_classnames();
        return $types[$type] ?? null;
    }

    /**
     * Returns list of all type names.
     *
     * @return string[] type => type name
     */
    final public static function get_type_names(): array {
        $classes = self::get_type_classnames();

        $result = [];
        foreach ($classes as $class) {
            $result[$class::get_type()] = $class::get_type_name();
        }

        return $result;
    }

    /**
     * Return item type string.
     *
     * @return string
     */
    public static function get_type(): string {
        return static::TYPE;
    }

    /**
     * Is item type available.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return true;
    }

    /**
     * Return item creation form class name.
     *
     * @return class-string<\tool_mulib\local\ajax_form>
     */
    public static function get_create_form_class(): string {
        return "tool_mucatalog\\local\\form\\items_create";
    }

    /**
     * Return item creation form autocmplete referenceids class name.
     *
     * @return class-string<\tool_mulib\external\form_autocomplete\base>
     */
    public static function get_create_form_referenceids_class(): string {
        $type = static::get_type();
        return "tool_mucatalog\\external\\form_autocomplete\\items_create_{$type}ids";
    }

    /**
     * Returns name of the item type.
     *
     * @return string
     */
    public static function get_type_name(): string {
        $type = static::get_type();
        return get_string('item_type_' . $type, 'tool_mucatalog');
    }

    /**
     * Create catalog item.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;
        $classname = self::get_type_classname($data->type);
        if (!$classname) {
            throw new invalid_parameter_exception('unknown item type');
        }
        if ($classname !== static::class) {
            throw new coding_exception('invalid item create usage');
        }

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $data->sectionid], '*', MUST_EXIST);

        $now = time();

        if (!isset($data->syncname)) {
            if (trim($data->name ?? '') === '') {
                $data->syncname = 1;
            } else {
                $data->syncname = 0;
            }
        }

        $record = (object)[
            'sectionid' => $section->id,
            'type' => $data->type,
            'referenceid' => $data->referenceid ?? null,
            'syncname' => (int)(bool)($data->syncname ?? 1),
            'name' => $data->name ?? '',
            'presentationjson' => '{}',
            'hiddenbefore' => empty($data->hiddenbefore) ? null : (int)$data->hiddenbefore,
            'hiddenafter' => empty($data->hiddenafter) ? null : (int)$data->hiddenafter,
            'status' => (int)($data->status ?? util::STATUS_DRAFT),
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $options = util::get_statuses_menu();
        if (!isset($options[$record->status])) {
            throw new invalid_parameter_exception('invalid item status');
        }

        static::pre_create($record, $section, $data);

        if (trim($record->name) === '') {
            throw new invalid_parameter_exception('item name is required');
        }
        if (\core_text::strlen($record->name) > 254) {
            $record->name = \core_text::substr($record->name, 254);
        }

        $trans = $DB->start_delegated_transaction();

        $record->id = $DB->insert_record('tool_mucatalog_item', $record);
        $record = $DB->get_record('tool_mucatalog_item', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        return $record;
    }

    /**
     * Prepare record for item creation.
     *
     * @param stdClass $record
     * @param stdClass $section
     * @param stdClass $data submitted data
     * @return void
     */
    abstract protected static function pre_create(stdClass $record, stdClass $section, stdClass $data): void;

    /**
     * Create catalog items.
     *
     * @param stdClass $data
     * @return array item records
     */
    public static function create_multiple(stdClass $data): array {
        $result = [];
        foreach ($data->referenceids as $referenceid) {
            $itemdata = clone($data);
            unset($itemdata->referenceids);
            $itemdata->referenceid = $referenceid;
            $itemdata->syncname = 1;
            $item = static::create($itemdata);
            $result[$item->id] = $item;
        }
        return $result;
    }

    /**
     * Update catalog item.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $oldrecord = $DB->get_record('tool_mucatalog_item', ['id' => $data->id], '*', MUST_EXIST);
        $section = $DB->get_record('tool_mucatalog_section', ['id' => $oldrecord->sectionid], '*', MUST_EXIST);

        $classname = self::get_type_classname($oldrecord->type);
        if (!$classname) {
            throw new invalid_parameter_exception('unknown item type');
        }
        if ($classname !== static::class) {
            throw new coding_exception('invalid item update usage');
        }

        $now = time();

        $record = (object)[
            'id' => $oldrecord->id,
        ];

        if (property_exists($data, 'syncname')) {
            $record->syncname = (int)(bool)$data->syncname;
        } else {
            $record->syncname = (int)(bool)$oldrecord->syncname;
        }
        if (!$record->syncname) {
            if (property_exists($data, 'name')) {
                if (trim($data->name ?? '') === '') {
                    throw new invalid_parameter_exception('item name is required');
                }
                $record->name = $data->name;
            }
        }

        if (property_exists($data, 'hiddenbefore')) {
            $record->hiddenbefore = empty($data->hiddenbefore) ? null : (int)$data->hiddenbefore;
        }

        if (property_exists($data, 'hiddenafter')) {
            $record->hiddenafter = empty($data->hiddenafter) ? null : (int)$data->hiddenafter;
        }

        if ($oldrecord->status == util::STATUS_DRAFT) {
            if (property_exists($data, 'status')) {
                if ($oldrecord->status == util::STATUS_ACTIVE) {
                    $record->status = util::STATUS_ACTIVE;
                } else {
                    throw new invalid_parameter_exception('item status change');
                }
            }
        }

        $record->timemodified = $now;

        static::pre_update($record, $section, $data, $oldrecord);

        if (property_exists($record, 'name') && \core_text::strlen($record->name) > 254) {
            $record->name = \core_text::substr($record->name, 254);
        }

        $trans = $DB->start_delegated_transaction();

        $DB->update_record('tool_mucatalog_item', $record);
        $record = $DB->get_record('tool_mucatalog_item', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        return $record;
    }

    /**
     * Prepare record for item update.
     *
     * @param stdClass $record
     * @param stdClass $section
     * @param stdClass $data submitted data
     * @param stdClass $oldrecord
     * @return void
     */
    abstract protected static function pre_update(stdClass $record, stdClass $section, stdClass $data, stdClass $oldrecord): void;

    /**
     * Is item activation possible?
     *
     * @param stdClass $item
     * @return bool
     */
    public static function is_activate_possible(stdClass $item): bool {
        if ($item->type !== static::TYPE) {
            throw new coding_exception('incorrect type class used');
        }
        return ($item->status == util::STATUS_DRAFT);
    }

    /**
     * Activate draft catalog item.
     *
     * @param int $id
     * @return stdClass
     */
    final public static function activate(int $id): stdClass {
        global $DB;

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $id], '*', MUST_EXIST);
        if ($item->type !== static::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        if ($item->status == util::STATUS_ACTIVE) {
            return $item;
        }

        if ($item->status != util::STATUS_DRAFT) {
            throw new invalid_parameter_exception('only draft items can be activated');
        }

        $record = [
            'id' => $item->id,
            'status' => util::STATUS_ACTIVE,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_item', $record);

        return $DB->get_record('tool_mucatalog_item', ['id' => $item->id], '*', MUST_EXIST);
    }

    /**
     * Is item archiving possible?
     *
     * @param stdClass $item
     * @return bool
     */
    public static function is_archive_possible(stdClass $item): bool {
        if ($item->type !== static::TYPE) {
            throw new coding_exception('incorrect type class used');
        }
        return ($item->status == util::STATUS_ACTIVE);
    }

    /**
     * Archive active catalog item.
     *
     * @param int $id
     * @return stdClass
     */
    final public static function archive(int $id): stdClass {
        global $DB;

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $id], '*', MUST_EXIST);
        if ($item->type !== static::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        if ($item->status == util::STATUS_ARCHIVED) {
            return $item;
        }

        if ($item->status != util::STATUS_ACTIVE) {
            throw new invalid_parameter_exception('only active items can be archived');
        }

        $record = [
            'id' => $item->id,
            'status' => util::STATUS_ARCHIVED,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_item', $record);

        return $DB->get_record('tool_mucatalog_item', ['id' => $item->id], '*', MUST_EXIST);
    }


    /**
     * Is item restoring possible?
     *
     * @param stdClass $item
     * @return bool
     */
    public static function is_restore_possible(stdClass $item): bool {
        if ($item->type !== static::TYPE) {
            throw new coding_exception('incorrect type class used');
        }
        return ($item->status == util::STATUS_ARCHIVED);
    }

    /**
     * Restore archived catalog item.
     *
     * @param int $id
     * @return stdClass
     */
    final public static function restore(int $id): stdClass {
        global $DB;

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $id], '*', MUST_EXIST);
        if ($item->status == util::STATUS_ACTIVE) {
            return $item;
        }

        if ($item->status != util::STATUS_ARCHIVED) {
            throw new invalid_parameter_exception('only archived items can be restored');
        }

        $record = [
            'id' => $item->id,
            'status' => util::STATUS_ACTIVE,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_item', $record);

        return $DB->get_record('tool_mucatalog_item', ['id' => $item->id], '*', MUST_EXIST);
    }

    /**
     * Move catalog item.
     *
     * @param int $id
     * @param int $newsectionid
     * @return stdClass
     */
    final public static function move(int $id, int $newsectionid): stdClass {
        global $DB;

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $id], '*', MUST_EXIST);
        $newsection = $DB->get_record('tool_mucatalog_section', ['id' => $newsectionid], '*', MUST_EXIST);

        if ($item->sectionid == $newsection->id) {
            return $item;
        }

        $record = [
            'id' => $item->id,
            'sectionid' => $newsection->id,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mucatalog_item', $record);

        return $DB->get_record('tool_mucatalog_item', ['id' => $item->id], '*', MUST_EXIST);
    }

    /**
     * Is item deletion possible?
     *
     * @param stdClass $item
     * @return bool
     */
    public static function is_delete_possible(stdClass $item): bool {
        return true;
    }

    /**
     * Delete catalog item.
     *
     * @param int $id
     * @return void
     */
    final public static function delete(int $id): void {
        global $DB;

        $item = $DB->get_record('tool_mucatalog_item', ['id' => $id]);
        if (!$item) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('tool_mucatalog_collection_item', ['itemid' => $item->id]);

        $DB->delete_records('tool_mucatalog_item', ['id' => $item->id]);

        $trans->allow_commit();
    }

    /**
     * Returns formatted description.
     *
     * @param stdClass $item
     * @return string|null HTML fragment
     */
    public static function get_description(stdClass $item): ?string {
        return null;
    }

    /**
     * Returns link to item - management UI stuff.
     *
     * @param int $itemid
     * @return string HTML fragment with a link or error info
     */
    abstract public static function get_reference(int $itemid): string;

    /**
     * Returns item image URL.
     *
     * @param stdClass $item
     * @return string
     */
    abstract public static function get_image_url(stdClass $item): string;

    /**
     * Is given user registered - active enrolments, assigned, etc.
     *
     * @param stdClass $item
     * @param int $userid
     * @return bool
     */
    abstract public static function is_user_registered(stdClass $item, int $userid): bool;

    /**
     * Returns item opening URL.
     *
     * @param stdClass $item
     * @return url|null
     */
    public static function get_open_url(stdClass $item): ?url {
        return null;
    }

    /**
     * Send pluginfile.php item image.
     *
     * @param stdClass $item
     */
    abstract public static function send_image(stdClass $item): never;

    /**
     * Send pluginfile.php description file.
     *
     * @param stdClass $item
     * @param string $filepath
     * @param string $filename
     */
    abstract public static function send_description_file(stdClass $item, string $filepath, string $filename): never;

    /**
     * Send pluginfile.php generated pattern image.
     *
     * @param stdClass $item
     */
    abstract public static function send_geopattern(stdClass $item): never;
}
