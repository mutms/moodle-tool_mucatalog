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

namespace tool_mucatalog\local\item;

use tool_mucatalog\local\item;
use stdClass;
use tool_mucatalog\local\util;
use core\exception\coding_exception;
use core\url;
use tool_mulib\local\sql;

/**
 * Certification item.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification extends item {
    /** @var string item type */
    public const TYPE = 'certification';

    /**
     * Is item type available.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return \tool_mulib\local\mulib::is_mucertify_available();
    }

    /**
     * Prepare record for item creation.
     *
     * @param stdClass $record
     * @param stdClass $section
     * @param stdClass $data
     * @return void
     */
    protected static function pre_create(stdClass $record, stdClass $section, stdClass $data): void {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $record->referenceid], '*', MUST_EXIST);

        if ($record->syncname) {
            $record->name = $certification->fullname;
        }
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
    protected static function pre_update(stdClass $record, stdClass $section, stdClass $data, stdClass $oldrecord): void {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $oldrecord->referenceid]);
        if (!$certification) {
            return;
        }

        if ($record->syncname) {
            $record->name = $certification->fullname;
        }
    }

    /**
     * Is item restoring possible?
     *
     * @param stdClass $item
     * @return bool
     */
    public static function is_restore_possible(stdClass $item): bool {
        global $DB;

        if (!parent::is_restore_possible($item)) {
            return false;
        }

        if (!parent::is_available()) {
            return false;
        }

        $sql = "SELECT 'x'
                  FROM {tool_mucatalog_item} i
                  JOIN {tool_mucertify_certification} c ON c.id = i.referenceid
                 WHERE i.id = :id AND i.type = 'certification'";
        return $DB->record_exists_sql($sql, ['id' => $item->id]);
    }

    /**
     * certification updated observer - syncs item names.
     *
     * @param \tool_mucertify\event\certification_updated $event
     * @return void
     */
    public static function event_certification_updated(\tool_mucertify\event\certification_updated $event): void {
        global $DB;

        $certification = $event->get_record_snapshot('tool_mucertify_certification', $event->objectid);

        $sql = "SELECT i.id
                  FROM {tool_mucatalog_item} i
                 WHERE i.type = 'certification' AND i.referenceid = :certificationid AND i.syncname = 1 AND i.name <> :name
              ORDER BY i.id ASC";
        $ids = $DB->get_fieldset_sql($sql, ['certificationid' => $certification->id, 'name' => $certification->fullname]);
        foreach ($ids as $id) {
            $name = $certification->fullname;
            if (\core_text::strlen($name) > 254) {
                $name = \core_text::substr($name, 254);
            }
            $DB->set_field('tool_mucatalog_item', 'name', $name, ['id' => $id]);
        }
    }

    /**
     * certification deleted observer - archives/deletes items.
     *
     * @param \tool_mucertify\event\certification_deleted $event
     * @return void
     */
    public static function event_certification_deleted(\tool_mucertify\event\certification_deleted $event): void {
        global $DB;

        $certificationid = $event->objectid;

        $sql = "SELECT i.id, i.status
                  FROM {tool_mucatalog_item} i
                 WHERE i.type = 'certification' AND i.referenceid = :certificationid AND i.status <> :archived
              ORDER BY i.id ASC";
        $rs = $DB->get_recordset_sql($sql, ['certificationid' => $certificationid, 'archived' => util::STATUS_ARCHIVED]);
        foreach ($rs as $item) {
            if ($item->status == util::STATUS_DRAFT) {
                self::delete($item->id);
            } else {
                $DB->set_field('tool_mucatalog_item', 'referenceid', null, ['id' => $item->id]);
                self::archive($item->id);
            }
        }
    }

    /**
     * Returns formatted description.
     *
     * @param stdClass $item
     * @return string|null HTML fragment
     */
    public static function get_description(stdClass $item): ?string {
        global $DB;

        if ($item->type !== self::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $item->referenceid]);
        if (!$certification || trim($certification->description) === '') {
            return null;
        }

        $syscontext = \context_system::instance();

        $description = file_rewrite_pluginfile_urls($certification->description, 'pluginfile.php', $syscontext->id, 'tool_mucatalog', 'item_description', $item->id);
        return format_text($description, $certification->descriptionformat, ['context' => $syscontext]);
    }

    /**
     * Returns link to item - management UI stuff.
     *
     * @param int $itemid
     * @return string HTML fragment with a link or error info
     */
    public static function get_reference(int $itemid): string {
        global $DB;

        $sql = "SELECT c.id, c.fullname, c.contextid
                  FROM {tool_mucatalog_item} i
                  JOIN {tool_mucertify_certification} c ON c.id = i.referenceid
                 WHERE i.id = :id AND i.type = 'certification'";
        $certification = $DB->get_record_sql($sql, ['id' => $itemid]);
        if (!$certification) {
            return get_string('error');
        }
        $name = format_string($certification->fullname);

        $context = \context::instance_by_id($certification->contextid, IGNORE_MISSING);
        if ($context && has_capability('tool/mucertify:view', $context)) {
            $url = new url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
            $name = \html_writer::link($url, $name);
        }

        return $name;
    }

    /**
     * Returns item image URL.
     *
     * @param stdClass $item
     * @return string
     */
    public static function get_image_url(stdClass $item): string {
        global $DB;

        if ($item->type !== self::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        $syscontext = \context_system::instance();

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $item->referenceid], 'id, presentationjson');
        if ($certification) {
            $presentation = (array)json_decode($certification->presentationjson);
            if (!empty($presentation['image'])) {
                return url::make_pluginfile_url($syscontext->id, 'tool_mucatalog', 'item_image', $item->id, '/', $presentation['image'])->out(false);
            }
        }

        return url::make_pluginfile_url($syscontext->id, 'tool_mucatalog', 'item_image', $item->id, '/', 'geopattern.svg')->out(false);
    }

    /**
     * Send pluginfile.php item image.
     *
     * @param stdClass $item
     * @return never
     */
    public static function send_image(stdClass $item): never {
        global $DB;

        if ($item->type !== self::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        if (!self::is_available()) {
            send_file_not_found();
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $item->referenceid], 'id, presentationjson');
        if (!$certification) {
            send_file_not_found();
        }

        $syscontext = \context_system::instance();
        $presentation = (array)json_decode($certification->presentationjson);
        if (!empty($presentation['image'])) {
            $fs = get_file_storage();
            $file = $fs->get_file($syscontext->id, 'tool_mucertify', 'image', $certification->id, '/', $presentation['image']);
            if ($file) {
                send_stored_file($file, 50 * 60, 0, true);
            }
        }

        send_file_not_found();
    }

    /**
     * Send pluginfile.php description file.
     *
     * @param stdClass $item
     * @param string $filepath
     * @param string $filename
     * @return never
     */
    public static function send_description_file(stdClass $item, string $filepath, string $filename): never {
        global $DB, $USER;

        if ($item->type !== self::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        if (!self::is_available()) {
            send_file_not_found();
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $item->referenceid]);
        if (!$certification) {
            send_file_not_found();
        }
        $context = \context::instance_by_id($certification->contextid);

        $section = $DB->get_record('tool_mucatalog_section', ['id' => $item->sectionid]);
        if (!$section) {
            send_file_not_found();
        }
        $sectioncontext = \context::instance_by_id($section->contextid);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
        } else {
            $tenantid = null;
        }

        if (
            !has_capability('tool/mucertify:view', $context)
            && !has_capability('tool/mucatalog:view', $sectioncontext)
            && !\tool_mucatalog\local\catalogue::is_item_visible($item, $section, $USER->id, $tenantid)
        ) {
            send_file_not_found();
        }

        $syscontext = \context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file($syscontext->id, 'tool_mucertify', 'description', $certification->id, $filepath, $filename);
        if ($file) {
            send_stored_file($file, 60 * 60, 0, true);
        }
        send_file_not_found();
    }

    /**
     * Send pluginfile.php generated pattern image.
     *
     * @param stdClass $item
     */
    public static function send_geopattern(stdClass $item): never {
        $geopattern = \tool_mucertify\local\certification::get_image_geopattern($item->referenceid);
        send_file($geopattern->toSVG(), 'geopattern.svg', 7 * DAYSECS, 0, true);
        exit;
    }

    /**
     * Is given user registered - enrolled, assigned, etc.
     *
     * @param stdClass $item
     * @param int $userid
     * @return bool
     */
    public static function is_user_registered(stdClass $item, int $userid): bool {
        global $DB;

        if ($item->type !== self::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        if (!$userid || isguestuser($userid)) {
            return false;
        }

        $sql = new sql(
            "SELECT 'x'
               FROM {tool_mucertify_assignment} ca
               JOIN {tool_mucertify_certification} c ON c.id  = ca.certificationid
              WHERE ca.userid = :userid AND c.id = :certificationid AND c.archived = 0 AND ca.archived = 0",
            ['userid' => $userid, 'certificationid' => $item->referenceid]
        );

        return $DB->record_exists_sql($sql->sql, $sql->params);
    }

    /**
     * Returns item opening URL.
     *
     * @param stdClass $item
     * @return url|null
     */
    public static function get_open_url(stdClass $item): ?url {
        global $DB, $USER;

        if ($item->type !== self::TYPE) {
            throw new coding_exception('incorrect type class used');
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $item->referenceid]);
        if (!$certification) {
            return null;
        }
        $context = \context::instance_by_id($certification->contextid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($context->tenantid && $tenantid && $context->tenantid != $tenantid) {
                return null;
            }
        }

        if (self::is_user_registered($item, $USER->id)) {
            return new url('/admin/tool/mucertify/my/certification.php', ['id' => $certification->id]);
        }

        if (has_capability('tool/mucertify:view', $context)) {
            return new url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
        }

        // Ignore old catalog for now, no self-assignment either.

        return null;
    }
}
