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

namespace tool_mucatalog\navigation\views;

use stdClass;
use core\url;

/**
 * Collection management page secondary menu.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collection_secondary extends \core\navigation\views\secondary {
    /** @var stdClass */
    protected $collection;

    /**
     * Navigation constructor.
     * @param \moodle_page $page
     * @param stdClass $collection
     */
    public function __construct(\moodle_page $page, stdClass $collection) {
        parent::__construct($page);
        $this->collection = $collection;
    }

    /**
     * Init secondary menu.
     */
    public function initialise(): void {
        $this->id = 'secondary_navigation';
        $this->headertitle = get_string('menu');

        $collection = $this->collection;

        $url = new url('/admin/tool/mucatalog/management/collection.php', ['id' => $collection->id]);
        $this->add(get_string('tabgeneral', 'tool_mucatalog'), $url, \navigation_node::TYPE_SETTING, null, 'collection_general');

        $url = new url('/admin/tool/mucatalog/management/collection_items.php', ['id' => $collection->id]);
        $this->add(get_string('tabitems', 'tool_mucatalog'), $url, \navigation_node::TYPE_SETTING, null, 'collection_items');

        $this->scan_for_active_node($this);
        $this->initialised = true;
    }
}
