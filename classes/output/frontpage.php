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
use core\url;

/**
 * Class containing data for Catalogue frontpage.
 *
 * @package   tool_mucatalog
 * @copyright 2026 Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class frontpage implements \core\output\named_templatable, \core\output\renderable {
    /** @var array */
    protected $sections;
    /** @var array */
    protected $collections;

    /**
     * Constructor.
     */
    public function __construct() {
        global $USER;

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
        } else {
            $tenantid = null;
        }

        $this->sections = catalogue::get_sections($USER->id, true, $tenantid);
        $this->collections = catalogue::get_collections($USER->id, true, $tenantid);
    }

    /**
     * Is the frontpage usable - does it have anything special to show?
     * @return bool
     */
    public function is_usable(): bool {
        return (count($this->sections) > 1 || $this->collections);
    }

    /**
     * Export template data.
     *
     * @param \core\output\renderer_base $output
     * @return array
     */
    public function export_for_template(\core\output\renderer_base $output): array {
        $data = [];

        $data[] = (object)[
            'isall' => true,
            'name' => get_string('items_all', 'tool_mucatalog'),
            'shortdescription' => catalogue::format_short_description(get_string('items_all_desc', 'tool_mucatalog')),
            'url' => (new url('/admin/tool/mucatalog/', ['sectionid' => 0]))->out(false),
            'frontpagepriority' => 0,
        ];

        foreach ($this->sections as $section) {
            $data[] = (object)[
                'issection' => true,
                'name' => format_string($section->name),
                'shortdescription' => catalogue::format_short_description($section->shortdescription),
                'url' => (new url('/admin/tool/mucatalog/', ['sectionid' => $section->id]))->out(false),
                'frontpagepriority' => $section->frontpagepriority,
            ];
        }

        foreach ($this->collections as $collection) {
            $data[] = (object)[
                'iscollection' => true,
                'name' => format_string($collection->name),
                'shortdescription' => catalogue::format_short_description($collection->shortdescription),
                'url' => (new url('/admin/tool/mucatalog/', ['sectionid' => -1 * (int)$collection->id]))->out(false),
                'frontpagepriority' => $collection->frontpagepriority,
            ];
        }

        \core_collator::asort_objects_by_property($data, 'frontpagepriority', \core_collator::SORT_NUMERIC);
        $data = array_reverse($data);

        return [
            'sections' => array_values($data),
        ];
    }

    /**
     * Template name.
     *
     * @param \core\output\renderer_base $renderer
     * @return string
     */
    public function get_template_name(\core\output\renderer_base $renderer): string {
        return 'tool_mucatalog/frontpage';
    }
}
