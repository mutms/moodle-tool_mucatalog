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

namespace tool_mucatalog\output;

use tool_mucatalog\local\catalogue;
use tool_mucatalog\local\item;
use core\url;
use stdClass;

/**
 * Class containing data for Catalogue browsing.
 *
 * @package   tool_mucatalog
 * @copyright 2026 Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class browse implements \core\output\named_templatable, \core\output\renderable {
    /** @var int */
    protected $sectionid;

    /**
     * Constructor.
     *
     * @param int $sectionid
     */
    public function __construct(int $sectionid) {
        $this->sectionid = $sectionid;
    }

    /**
     * Export template data.
     *
     * @param \core\output\renderer_base $output
     * @return array
     */
    public function export_for_template(\core\output\renderer_base $output): array {
        global $USER;

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
        } else {
            $tenantid = null;
        }

        $data = [
            'sectionid' => $this->sectionid,
            'hidesectionselect' => true,
            'sectionselect' => [
                'all' => [
                    'name' => get_string('items_all', 'tool_mucatalog'),
                    'value' => 0,
                    'iscurrent' => ($this->sectionid == 0),
                ],
                'hascollections' => false,
                'collections' => [],
                'sections' => [],
            ],
            'typeselect' => [],
            'hastypeselect' => false,
            'items' => [],
            'hasmore' => false,
        ];

        $collections = catalogue::get_collections($USER->id, false, $tenantid);
        if ($collections) {
            foreach ($collections as $collection) {
                $sid = -1 * (int)$collection->id;
                $data['sectionselect']['collections'][] = [
                    'name' => format_string($collection->name),
                    'value' => $sid,
                    'iscurrent' => ($this->sectionid == $sid),
                ];
            }
            $data['sectionselect']['hascollections'] = true;
            $data['hidesectionselect'] = false;
        }

        $sections = catalogue::get_sections($USER->id, false, $tenantid);
        if (count($sections) > 1) {
            foreach ($sections as $section) {
                $data['sectionselect']['sections'][] = [
                    'name' => format_string($section->name),
                    'value' => $section->id,
                    'iscurrent' => ($this->sectionid == (int)$section->id),
                ];
            }
            $data['sectionselect']['hassections'] = true;
            $data['hidesectionselect'] = false;
        } else {
            if ($this->sectionid > 0) {
                $data['sectionid'] = 0;
            }
        }

        $typeclasses = item::get_type_classnames();
        foreach ($typeclasses as $type => $typeclass) {
            if (!$typeclass::is_available()) {
                unset($typeclasses[$type]);
            }
        }
        if (count($typeclasses) > 1) {
            $data['typeselect'][] = [
                'name' => get_string('item_type_any', 'tool_mucatalog'),
                'value' => '',
                'iscurrent' => true,
            ];
            foreach ($typeclasses as $type => $typeclass) {
                $data['typeselect'][] = [
                    'name' => $typeclass::get_type_name(),
                    'value' => $type,
                    'iscurrent' => false,
                ];
            }
            $data['hastypeselect'] = true;
        }

        $rawitems = catalogue::fetch_items($this->sectionid, $USER->id, $tenantid, catalogue::ITEMS_BY_NAME, 0, catalogue::ITEMS_PER_PAGE + 1);
        if (count($rawitems) > catalogue::ITEMS_PER_PAGE) {
            $rawitems = array_slice($rawitems, 0, catalogue::ITEMS_PER_PAGE, true);
            $data['hasmore'] = true;
        }
        $data['nextlimitfrom'] = catalogue::ITEMS_PER_PAGE;

        foreach ($rawitems as $rawitem) {
            $itemdata = self::format_item_data($rawitem);
            if (!$itemdata) {
                continue;
            }
            $data['items'][] = $itemdata;
        }

        return $data;
    }

    /**
     * Format item data for "tool_muprog/browse-item" template.
     *
     * @param stdClass $item item record with additional 'senctionname' field
     * @return array|null
     */
    public static function format_item_data(stdClass $item): ?array {
        global $USER;

        $classname = item::get_type_classname($item->type);
        if (!$classname) {
            return null;
        }
        if (!$classname::is_available()) {
            // This should not happen.
            return null;
        }
        return [
            'itemtype' => $classname::get_type_name(),
            'itemname' => format_string($item->name),
            'sectionname' => format_string($item->sectionname),
            'itemurl' => (new url('/admin/tool/mucatalog/item.php', ['id' => $item->id]))->out(false),
            'imageurl' => $classname::get_image_url($item),
            'registered' => $classname::is_user_registered($item, $USER->id),
        ];
    }

    /**
     * Template name.
     *
     * @param \core\output\renderer_base $renderer
     * @return string
     */
    public function get_template_name(\core\output\renderer_base $renderer): string {
        return 'tool_mucatalog/browse';
    }
}
