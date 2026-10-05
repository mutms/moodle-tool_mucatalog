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
 * Hook callbacks from tool_mutenancy related code.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_mutenancy {
    /**
     * Tenant management menu hook.
     *
     * @param \tool_mutenancy\hook\tenant_management_menu $hook
     */
    public static function hook_tenant_management_menu(\tool_mutenancy\hook\tenant_management_menu $hook): void {
        // NOTE: do not require active catalogue here, tenant managers need to be able to create the first section.
        if (!has_capability('tool/mucatalog:view', $hook->catcontext)) {
            return;
        }

        $url = new url('/admin/tool/mucatalog/management/sections.php', ['contextid' => $hook->catcontext->id]);
        $hook->tenantnode->add(
            get_string('management_catalogue', 'tool_mucatalog'),
            $url
        );
    }
}
