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

namespace tool_mucatalog\callback;

use core\url;

/**
 * Hook and event callbacks from core related code.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class core {
    /**
     * Primary menu integration hook.
     *
     * @param \core\hook\navigation\primary_extend $hook
     * @return void
     */
    public static function hook_primary_extend(\core\hook\navigation\primary_extend $hook): void {
        if (!is_callable([\tool_mulib\local\mulib::class, 'is_mucatalog_active'])) {
            return;
        }

        if (!\tool_mulib\local\mulib::is_mucatalog_active()) {
            return;
        }

        $addmenu = get_config('tool_mucatalog', 'addmenu');
        if (!$addmenu) {
            return;
        }

        if (!isloggedin() || isguestuser()) {
            if (!get_config('tool_mucatalog', 'guestvisible')) {
                return;
            }
        }

        $beforekey = null;
        $primary = $hook->get_primaryview();

        if ($primary->find('siteadminnode', null)) {
            $beforekey = 'siteadminnode';
        } else if ($primary->find('tool_mutenancy', null)) {
            $beforekey = 'tool_mutenancy';
        }

        $title = get_string('catalogue_title', 'tool_mucatalog');
        $catalognode = \navigation_node::create(
            $title,
            new url('/admin/tool/mucatalog/'),
            $primary::TYPE_CUSTOM,
            $title,
            'tool_mucatalog'
        );

        $primary->add_node($catalognode, $beforekey);
    }
}
