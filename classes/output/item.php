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

use core\url;
use stdClass;

/**
 * Class containing data for item view.
 *
 * @package   tool_mucatalog
 * @copyright 2026 Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item implements \core\output\named_templatable, \core\output\renderable {
    /** @var stdClass */
    protected $item;
    /** @var stdClass */
    protected $section;

    /**
     * Constructor.
     *
     * @param stdClass $item
     * @param stdClass $section
     */
    public function __construct(stdClass $item, stdClass $section) {
        $this->item = $item;
        $this->section = $section;
    }

    /**
     * Export template data.
     *
     * @param \core\output\renderer_base $output
     * @return array
     */
    public function export_for_template(\core\output\renderer_base $output): array {
        return self::format_item_data($this->item, $this->section);
    }

    /**
     * Format item data for "tool_muprog/item" template.
     *
     * @param stdClass $item
     * @param stdClass $section
     * @return array|null
     */
    public static function format_item_data(stdClass $item, stdClass $section): ?array {
        global $USER;

        $classname = \tool_mucatalog\local\item::get_type_classname($item->type);
        if (!$classname) {
            return null;
        }
        if (!$classname::is_available()) {
            // This should not happen.
            return null;
        }

        $openurl = $classname::get_open_url($item);

        return [
            'itemid' => $item->id,
            'itemtype' => $classname::get_type_name(),
            'itemname' => format_string($item->name),
            'sectionname' => format_string($section->name),
            'openurl' => $openurl,
            'imageurl' => $classname::get_image_url($item),
            'registered' => $classname::is_user_registered($item, $USER->id),
            'description' => $classname::get_description($item),
        ];
    }

    /**
     * Template name.
     *
     * @param \core\output\renderer_base $renderer
     * @return string
     */
    public function get_template_name(\core\output\renderer_base $renderer): string {
        return 'tool_mucatalog/item';
    }
}
