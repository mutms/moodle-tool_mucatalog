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
use core\url;
use core\exception\coding_exception;

/**
 * Universal catalogue browser.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class catalogue {
    /** @var int */
    public const ITEMS_PER_PAGE = 12;
    /** @var string */
    public const ITEMS_BY_NAME = 'name';

    /**
     * Returns sections user may see.
     *
     * @param int $userid
     * @param bool $frontpageonly
     * @param int|null $tenantid
     * @param string $fields
     * @return array
     */
    public static function get_visible_sections(int $userid, bool $frontpageonly, ?int $tenantid, string $fields = 's.*'): array {
        global $DB;

        $sql = new sql(
            "SELECT $fields
               FROM {tool_mucatalog_section} s
               JOIN {context} ctx ON ctx.id = s.contextid
               /* sectionvisiblejoin */
              WHERE s.status = :active
                    /* frontpageonly */
                    /* tenantwhere */
                    /* sectionvisiblewhere */
           ORDER BY s.name ASC",
            ['active' => util::STATUS_ACTIVE]
        );

        if ($frontpageonly) {
            $sql = $sql->replace_comment('frontpageonly', "AND s.frontpagepriority IS NOT NULL");
        } else {
            $sql = $sql->replace_comment('frontpageonly', "");
        }

        if (!$userid || isguestuser($userid)) {
            $sql = $sql->replace_comment('sectionvisiblejoin', "");
            $sql = $sql->replace_comment('sectionvisiblewhere', "AND s.guestvisible = 1");
        } else {
            $cte = new sql(
                "SELECT sv.sectionid
                   FROM {cohort_members} cm
                   JOIN {tool_mucatalog_section_cohortvisible} sv ON sv.cohortid = cm.cohortid
                  WHERE cm.userid = :userid
               GROUP BY sv.sectionid",
                ['userid' => $userid]
            );
            $cte = $cte->wrap("WITH cte_visible (sectionid) AS (", ")");
            $sql = sql::join("\n\n", [$cte, $sql]);
            $sql = $sql->replace_comment('sectionvisiblejoin', "LEFT JOIN cte_visible vs ON vs.sectionid = s.id");
            $sql = $sql->replace_comment('sectionvisiblewhere', "AND (s.uservisible = 1 OR vs.sectionid IS NOT NULL)");
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if ($tenantid) {
                $sql = $sql->replace_comment('tenantwhere', "AND (ctx.tenantid IS NULL OR ctx.tenantid = $tenantid) AND s.hiddenfromtenants = 0");
            } else {
                $sql = $sql->replace_comment('tenantwhere', "AND ctx.tenantid IS NULL");
            }
        } else {
            $sql = $sql->replace_comment('tenantwhere', "");
        }

        $sql->ensure_no_comments();

        return $DB->get_records_sql($sql->sql, $sql->params);
    }

    /**
     * Can user browse the section?
     *
     * @param stdClass|int $sectionorid section or sectionid
     * @param int $userid
     * @param int|null $tenantid
     * @return bool
     */
    public static function is_section_visible(stdClass|int $sectionorid, int $userid, ?int $tenantid): bool {
        global $DB;

        if (!$sectionorid) {
            debugging('$sectionorid parameter is required', DEBUG_DEVELOPER);
            return false;
        }

        if (is_object($sectionorid)) {
            $section = clone($sectionorid);
            if (!property_exists($section, 'presentationjson') || !property_exists($section, 'status')) {
                debugging('$sectionorid parameter is invalid object', DEBUG_DEVELOPER);
                return false;
            }
            if ($section->status <> util::STATUS_ACTIVE) {
                return false;
            }
        } else {
            $section = $DB->get_record('tool_mucatalog_section', ['id' => $sectionorid, 'status' => util::STATUS_ACTIVE]);
            if (!$section) {
                return false;
            }
        }

        $context = \context::instance_by_id($section->contextid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }

        if (!$userid || isguestuser($userid)) {
            if (!$section->guestvisible) {
                return false;
            }
        } else {
            if (!$section->uservisible) {
                $sql = new sql(
                    "SELECT 'x'
                       FROM {cohort_members} cm
                       JOIN {tool_mucatalog_section_cohortvisible} sv ON sv.cohortid = cm.cohortid
                      WHERE cm.userid = :userid AND sv.sectionid = :sectionid",
                    ['userid' => $userid, 'sectionid' => $section->id]
                );
                if (!$DB->record_exists_sql($sql->sql, $sql->params)) {
                    return false;
                }
            }
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if ($tenantid) {
                if ($context->tenantid) {
                    if ($context->tenantid != $tenantid) {
                        return false;
                    }
                } else {
                    if ($section->hiddenfromtenants) {
                        return false;
                    }
                }
            } else {
                if ($context->tenantid) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns collections user may see.
     *
     * @param int $userid
     * @param bool $frontpageonly
     * @param int|null $tenantid
     * @return array
     */
    public static function get_visible_collections(int $userid, bool $frontpageonly, ?int $tenantid): array {
        global $DB;

        $sql = new sql(
            "SELECT c.*
               FROM {tool_mucatalog_collection} c
               JOIN {context} ctx ON ctx.id = c.contextid
               /* sectionvisiblejoin */
              WHERE 1=1
                    /* frontpageonly */
                    /* tenantwhere */
                    /* sectionvisiblewhere */
           ORDER BY NAME",
            []
        );

        if ($frontpageonly) {
            $sql = $sql->replace_comment('frontpageonly', "AND c.frontpagepriority IS NOT NULL");
        } else {
            $sql = $sql->replace_comment('frontpageonly', "");
        }

        if (!$userid || isguestuser($userid)) {
            $sql = $sql->replace_comment('sectionvisiblejoin', "");
            $sql = $sql->replace_comment('sectionvisiblewhere', "AND c.guestvisible = 1");
        } else {
            $cte = new sql(
                "SELECT cv.collectionid
                   FROM {cohort_members} cm
                   JOIN {tool_mucatalog_collection_cohortvisible} cv ON cv.cohortid = cm.cohortid
                  WHERE cm.userid = :userid
               GROUP BY cv.collectionid",
                ['userid' => $userid]
            );
            $cte = $cte->wrap("WITH cte_visible (collectionid) AS (", ")");
            $sql = sql::join("\n\n", [$cte, $sql]);
            $sql = $sql->replace_comment('sectionvisiblejoin', "LEFT JOIN cte_visible vc ON vc.collectionid = c.id");
            $sql = $sql->replace_comment('sectionvisiblewhere', "AND (c.uservisible = 1 OR vc.collectionid IS NOT NULL)");
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if ($tenantid) {
                $sql = $sql->replace_comment('tenantwhere', "AND (ctx.tenantid IS NULL OR ctx.tenantid = $tenantid) AND s.hiddenfromtenants = 0");
            } else {
                $sql = $sql->replace_comment('tenantwhere', "AND ctx.tenantid IS NULL");
            }
        } else {
            $sql = $sql->replace_comment('tenantwhere', "");
        }

        $sql->ensure_no_comments();

        return $DB->get_records_sql($sql->sql, $sql->params);
    }

    /**
     * Can user browse the collection?
     *
     * @param stdClass|int $collectionorid collection or id
     * @param int $userid
     * @param int|null $tenantid
     * @return bool
     */
    public static function is_collection_visible(stdClass|int $collectionorid, int $userid, ?int $tenantid): bool {
        global $DB;

        if (!$collectionorid) {
            debugging('$collectionorid parameter is required', DEBUG_DEVELOPER);
            return false;
        }

        if (is_object($collectionorid)) {
            $collection = clone($collectionorid);
            if (!property_exists($collection, 'presentationjson') || property_exists($collection, 'status')) {
                debugging('$collectionorid parameter is invalid object', DEBUG_DEVELOPER);
                return false;
            }
        } else {
            $collection = $DB->get_record('tool_mucatalog_collection', ['id' => $collectionorid]);
            if (!$collection) {
                return false;
            }
        }

        $context = \context::instance_by_id($collection->contextid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }

        if (!$userid || isguestuser($userid)) {
            if (!$collection->guestvisible) {
                return false;
            }
        } else {
            if (!$collection->uservisible) {
                $sql = new sql(
                    "SELECT 'x'
                       FROM {cohort_members} cm
                       JOIN {tool_mucatalog_collection_cohortvisible} cv ON cv.cohortid = cm.cohortid
                      WHERE cm.userid = :userid AND cv.collectionid = :collectionid",
                    ['userid' => $userid, 'collectionid' => $collection->id]
                );
                if (!$DB->record_exists_sql($sql->sql, $sql->params)) {
                    return false;
                }
            }
        }
        $hiddenfromtenants = $collection->hiddenfromtenants;

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if ($tenantid) {
                if ($context->tenantid) {
                    if ($context->tenantid != $tenantid) {
                        return false;
                    }
                } else {
                    if ($hiddenfromtenants) {
                        return false;
                    }
                }
            } else {
                if ($context->tenantid) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns items user may see.
     *
     * @param int $sectionid positive int is section id, 0 is all visible items, negative is collection id (collection visibility is not verified)
     * @param int $userid
     * @param int|null $tenantid
     * @param string $orderby
     * @param int $limitfrom
     * @param int $limitnum
     * @param array $filters
     * @return array item records
     */
    public static function get_visible_items(int $sectionid, int $userid, ?int $tenantid, string $orderby, int $limitfrom, int $limitnum, array $filters = []): array {
        global $DB;

        if ($limitfrom < 0) {
            throw new \core\exception\invalid_parameter_exception('$limitfrom cannot be negative');
        }
        if ($limitnum < 1) {
            throw new \core\exception\invalid_parameter_exception('$limitnum must be positive');
        }

        $now = time();

        $sql = new sql(
            "SELECT i.*, s.name AS sectionname
               FROM {tool_mucatalog_item} i
               JOIN {tool_mucatalog_section} s ON s.id = i.sectionid
               /* sectionvisiblejoin */
               /* collectionjoin */
              WHERE s.status = :active1 AND i.status = :active2
                    /* sectionwhere */
                    AND (i.hiddenbefore IS NULL OR i.hiddenbefore < $now)
                    AND (i.hiddenafter IS NULL OR i.hiddenafter >= $now)
                    /* sectionvisiblewhere */
                    /* tenantwhere */
                    /* typewhere */
                    /* searchwhere */
           /* orderby */",
            ['active1' => util::STATUS_ACTIVE, 'active2' => util::STATUS_ACTIVE]
        );

        if ($sectionid > 0) {
            $sql = $sql->replace_comment('sectionwhere', "AND s.id = ?", [$sectionid]);
            $sql = $sql->replace_comment('collectionjoin', "");
        } else if ($sectionid < 0) {
            $sql = $sql->replace_comment('sectionwhere', "");
            $sql = $sql->replace_comment(
                'collectionjoin',
                "JOIN {tool_mucatalog_collection_item} ci ON ci.itemid = i.id AND ci.collectionid = ?",
                [-1 * $sectionid]
            );
        } else {
            $sql = $sql->replace_comment('sectionwhere', "");
            $sql = $sql->replace_comment('collectionjoin', "");
        }

        if (!$userid || isguestuser($userid)) {
            $sql = $sql->replace_comment('sectionvisiblejoin', "");
            $sql = $sql->replace_comment('sectionvisiblewhere', "AND s.guestvisible = 1");
        } else {
            $cte = new sql(
                "SELECT sv.sectionid
                   FROM {cohort_members} cm
                   JOIN {tool_mucatalog_section_cohortvisible} sv ON sv.cohortid = cm.cohortid
                  WHERE cm.userid = :userid
               GROUP BY sv.sectionid",
                ['userid' => $userid]
            );
            $cte = $cte->wrap("WITH cte_visible (sectionid) AS (", ")");
            $sql = sql::join("\n\n", [$cte, $sql]);
            $sql = $sql->replace_comment('sectionvisiblejoin', "LEFT JOIN cte_visible vs ON vs.sectionid = s.id");
            $sql = $sql->replace_comment('sectionvisiblewhere', "AND (s.uservisible = 1 OR vs.sectionid IS NOT NULL)");
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if ($tenantid) {
                $sql = $sql->replace_comment('tenantwhere', "AND (ctx.tenantid IS NULL OR ctx.tenantid = $tenantid) AND s.hiddenfromtenants = 0");
            } else {
                $sql = $sql->replace_comment('tenantwhere', "AND ctx.tenantid IS NULL");
            }
        } else {
            $sql = $sql->replace_comment('tenantwhere', "");
        }

        $search = '';
        $type = '';
        foreach ($filters as $filter) {
            ['field' => $field, 'value' => $value] = $filter;
            switch ($field) {
                case 'search':
                    $search = $value;
                    break;
                case 'type':
                    $type = $value;
                    break;
                default:
                    throw new \core\exception\invalid_parameter_exception('Unknown filter field: ' . $field);
            }
        }
        if ($search === '') {
            $sql = $sql->replace_comment('searchwhere', "");
        } else {
            $searchwhere = new sql($DB->sql_like('i.name', '?', false), ['%' . $DB->sql_like_escape($search) . '%']);
            $sql = $sql->replace_comment('searchwhere', $searchwhere->wrap("AND (", ')'));
        }
        if ($type === '') {
            $sql = $sql->replace_comment('typewhere', "");
        } else {
            $sql = $sql->replace_comment('typewhere', "AND i.type = :type", ['type' => $type]);
        }

        if ($orderby === self::ITEMS_BY_NAME) {
            $sql = $sql->replace_comment('orderby', "ORDER BY i.name ASC");
        } else {
            throw new coding_exception('Invalid orderby parameter');
        }

        $sql->ensure_no_comments();

        return $DB->get_records_sql($sql->sql, $sql->params, $limitfrom, $limitnum);
    }

    /**
     * Format short description.
     *
     * @param string $desc
     * @return string
     */
    public static function format_short_description(string $desc): string {
        return format_text($desc, FORMAT_MARKDOWN);
    }

    /**
     * Can current user browse the collection?
     *
     * @param stdClass|int $itemorid item or item id
     * @param stdClass|int $sectionorid section or section id
     * @param int $userid
     * @param int|null $tenantid
     * @return bool
     */
    public static function is_item_visible(stdClass|int $itemorid, stdClass|int $sectionorid, int $userid, ?int $tenantid): bool {
        global $DB;

        if (is_object($itemorid)) {
            $item = clone($itemorid);
            if (!property_exists($item, 'presentationjson') || !property_exists($item, 'status')) {
                debugging('$itemorid parameter is invalid object', DEBUG_DEVELOPER);
                return false;
            }
        } else {
            $item = $DB->get_record('tool_mucatalog_item', ['id' => $itemorid, 'status' => util::STATUS_ACTIVE]);
            if (!$item) {
                return false;
            }
        }

        if (is_object($sectionorid)) {
            if ($item->sectionid != $sectionorid->id) {
                throw new coding_exception('Invalid parameter mix');
            }
        } else {
            if ($item->sectionid != $sectionorid) {
                throw new coding_exception('Invalid parameter mix');
            }
        }

        if (!self::is_section_visible($sectionorid, $userid, $tenantid)) {
            return false;
        }

        if ($item->status <> util::STATUS_ACTIVE) {
            return false;
        }

        if ($item->hiddenbefore && $item->hiddenbefore > time()) {
            return false;
        }

        if ($item->hiddenafter && $item->hiddenafter <= time()) {
            return false;
        }

        return true;
    }
}
