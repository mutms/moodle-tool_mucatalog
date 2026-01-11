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

/**
 * Universal catalogue helpers.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class util {
    /** @var int drafts are not visible yet */
    public const STATUS_DRAFT = 0;
    /** @var int active have standard visibility */
    public const STATUS_ACTIVE = 1;
    /** @var int archived should be hidden in most places, they are not expected to be used in the future */
    public const STATUS_ARCHIVED = 2;

    /**
     * Returns menu of statuses for sections and items.
     *
     * @return array
     */
    public static function get_statuses_menu(): array {
        return [
            self::STATUS_DRAFT => get_string('status_draft', 'tool_mucatalog'),
            self::STATUS_ACTIVE => get_string('status_active', 'tool_mucatalog'),
            self::STATUS_ARCHIVED => get_string('status_archived', 'tool_mucatalog'),
        ];
    }
}
