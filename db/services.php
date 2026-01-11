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

/**
 * Universal catalogue services.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_mucatalog_form_autocomplete_section_contextid' => [
        'classname' => tool_mucatalog\external\form_autocomplete\section_contextid::class,
        'description' => 'Returns list of category context ids for catalogue sections.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_section_cohortvisible' => [
        'classname' => tool_mucatalog\external\form_autocomplete\section_cohortvisible::class,
        'description' => 'Returns list of visible cohort ids for catalogue section editing.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_collection_contextid' => [
        'classname' => tool_mucatalog\external\form_autocomplete\collection_contextid::class,
        'description' => 'Returns list of category context ids for catalogue collections.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_collection_cohortvisible' => [
        'classname' => tool_mucatalog\external\form_autocomplete\collection_cohortvisible::class,
        'description' => 'Returns list of visible cohort ids for catalogue collection editing.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_items_create_courseids' => [
        'classname' => tool_mucatalog\external\form_autocomplete\items_create_courseids::class,
        'description' => 'Returns list of course candidates for adding to section.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_items_create_programids' => [
        'classname' => tool_mucatalog\external\form_autocomplete\items_create_programids::class,
        'description' => 'Returns list of program candidates for adding to section.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_items_create_certificationids' => [
        'classname' => tool_mucatalog\external\form_autocomplete\items_create_certificationids::class,
        'description' => 'Returns list of certification candidates for adding to section.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_item_sectionid' => [
        'classname' => tool_mucatalog\external\form_autocomplete\item_sectionid::class,
        'description' => 'Returns list of section candidates for moving item.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_form_autocomplete_collection_itemids' => [
        'classname' => tool_mucatalog\external\form_autocomplete\collection_itemids::class,
        'description' => 'Returns list of item ids for adding to collection.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucatalog_get_items' => [
        'classname' => tool_mucatalog\external\get_items::class,
        'description' => 'Returns list of visible items',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => false, // It uses sessions, but we want to allow not-logged-in access.
    ],
];
