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

/**
 * Universal catalogue plugin upgrade.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade Universal catalogue.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_tool_mucatalog_upgrade($oldversion): bool {
    global $DB;

    if ($oldversion < 2026100553) {
        // Cached flag 'guestvisible' was renamed to 'hasguestsection',
        // it is set when there is at least one active section visible to guests.
        $hasguestsection = (int)$DB->record_exists('tool_mucatalog_section', ['status' => 1, 'guestvisible' => 1]);
        set_config('hasguestsection', $hasguestsection, 'tool_mucatalog');
        unset_config('guestvisible', 'tool_mucatalog');

        upgrade_plugin_savepoint(true, 2026100553, 'tool', 'mucatalog');
    }

    return true;
}
