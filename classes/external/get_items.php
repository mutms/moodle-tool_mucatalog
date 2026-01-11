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

namespace tool_mucatalog\external;

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use tool_mucatalog\local\catalogue;

/**
 * Provides list of items.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_items extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sectionid' => new external_value(PARAM_INT, 'section id or negative collection id, 0 means all sections'),
            'orderby' => new external_value(PARAM_ALPHANUMEXT, 'Ordering: '),
            'limitfrom' => new external_value(PARAM_INT, 'First item position to fetch'),
            'limitnum' => new external_value(PARAM_INT, '0 means default'),
            'filters' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'field' => new external_value(PARAM_ALPHANUMEXT, 'Name of filter'),
                        'value' => new external_value(PARAM_RAW, 'Value of filter'),
                    ]
                ),
                'Item filtering'
            ),
        ]);
    }

    /**
     * Execute.
     *
     * @param string $sectionid
     * @param string $orderby
     * @param int $limitfrom
     * @param int $limitnum
     * @param array $filters
     * @return array
     */
    public static function execute(string $sectionid, string $orderby, int $limitfrom, int $limitnum, array $filters = []): array {
        global $USER, $CFG, $PAGE;

        [
            'sectionid' => $sectionid,
            'orderby' => $orderby,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum,
            'filters' => $filters,
        ] = self::validate_parameters(self::execute_parameters(), [
            'sectionid' => $sectionid,
            'orderby' => $orderby,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum,
            'filters' => $filters,
        ]);

        // Do NOT use validate_context here, we want to allow not-logged-in access here!
        $syscontext = \context_system::instance();
        $PAGE->reset_theme_and_output();
        $PAGE->set_context($syscontext);

        if (!empty($CFG->forcelogin)) {
            require_login();
        }
        require_capability('tool/mucatalog:browse', $syscontext);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
        } else {
            $tenantid = null;
        }

        if ($sectionid > 0) {
            if (!catalogue::is_section_visible($sectionid, $USER->id, $tenantid)) {
                throw new \core\exception\invalid_parameter_exception('invalid sectionid parameter');
            }
        } else if ($sectionid < 0) {
            if (!catalogue::is_collection_visible(-1 * (int)$sectionid, $USER->id, $tenantid)) {
                throw new \core\exception\invalid_parameter_exception('invalid sectionid parameter');
            }
        }

        if ($orderby !== catalogue::ITEMS_BY_NAME) {
            throw new \core\exception\invalid_parameter_exception('invalid orderby parameter');
        }

        if ($limitnum < 1) {
            $limitnum = catalogue::ITEMS_PER_PAGE;
        }

        $hasmore = false;
        $rawitems = catalogue::fetch_items($sectionid, $USER->id, $tenantid, $orderby, $limitfrom, $limitnum + 1, $filters);
        if (count($rawitems) > $limitnum) {
            $rawitems = array_slice($rawitems, 0, $limitnum, true);
            $hasmore = true;
        }
        $nextlimitfrom = $limitfrom + $limitnum;

        $items = [];
        foreach ($rawitems as $rawitem) {
            $itemdata = \tool_mucatalog\output\browse::format_item_data($rawitem);
            if ($itemdata) {
                $items[] = $itemdata;
            }
        }

        return [
            'items' => $items,
            'nextlimitfrom' => $nextlimitfrom,
            'hasmore' => (int)$hasmore,
        ];
    }

    /**
     * Describes the external function result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'itemtype' => new external_value(PARAM_TEXT, 'Item type'),
                    'itemname' => new external_value(PARAM_TEXT, 'Item name'),
                    'sectionname' => new external_value(PARAM_TEXT, 'Item name'),
                    'itemurl' => new external_value(PARAM_URL, 'Item image URL'),
                    'imageurl' => new external_value(PARAM_URL, 'Item page URL'),
                    'registered' => new external_value(PARAM_BOOL, 'Is user enrolled?'),
                ])
            ),
            'nextlimitfrom' => new external_value(PARAM_INT, 'limitfrom to be used next time'),
            'hasmore' => new external_value(PARAM_INT, '1 means nextlimitfrom will return data, 0 means no more data'),
        ]);
    }
}
