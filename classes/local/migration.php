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

use core\exception\coding_exception;

/**
 * Migration of legacy catalogues from other plugins.
 *
 * NOTE: this API is called from upgrade steps of other plugins,
 *       the method signatures and behaviour must not change.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class migration {
    /**
     * Add program or certification from a legacy catalogue.
     *
     * Items with the same context and visibility are grouped into one migration section,
     * the section is created when needed. Sections visible to all users are active,
     * sections visible to cohorts are created as drafts to be reviewed.
     *
     * NOTE: this is intended for upgrade steps of tool_muprog and tool_mucertify only,
     *       Universal catalogue is guaranteed to be installed or upgraded before them.
     *
     * @param string $type item type, 'program' or 'certification'
     * @param int $referenceid program or certification id
     * @param int $contextid system or course category context of program or certification
     * @param bool $uservisible true means visible to all users, false means visible to cohorts
     * @param int[] $cohortids existing cohorts that may see the item, ignored if visible to all users
     */
    public static function add_migrated_item(string $type, int $referenceid, int $contextid, bool $uservisible, array $cohortids): void {
        global $DB;

        if ($type !== 'program' && $type !== 'certification') {
            throw new coding_exception('Invalid legacy catalogue item type');
        }
        $classname = item::get_type_classname($type);

        if ($uservisible) {
            $cohortids = [];
        } else {
            if (!$cohortids) {
                // Not visible to anybody in legacy catalogue.
                return;
            }
            // Ignore cohorts that do not exist any more.
            [$select, $params] = $DB->get_in_or_equal(array_map('intval', $cohortids));
            $cohorts = $DB->get_records_select_menu('cohort', "id $select", $params, 'id ASC', 'id, name');
            if (!$cohorts) {
                return;
            }
            $cohortids = array_map('intval', array_keys($cohorts));
        }

        $shortdescription = get_string('migration_' . $type . 's_desc', 'tool_mucatalog');
        if ($uservisible) {
            $name = get_string('migration_' . $type . 's_public', 'tool_mucatalog');
        } else {
            $name = get_string('migration_' . $type . 's_cohorts', 'tool_mucatalog', implode(', ', $cohorts));
        }
        if (\core_text::strlen($name) > 254) {
            $name = \core_text::substr($name, 0, 254);
        }

        // Find section created for previous items with the same context and visibility.
        $section = null;
        $candidates = $DB->get_records(
            'tool_mucatalog_section',
            ['contextid' => $contextid, 'name' => $name, 'uservisible' => (int)$uservisible, 'guestvisible' => 0],
            'id ASC'
        );
        foreach ($candidates as $candidate) {
            if ($candidate->shortdescription !== $shortdescription) {
                continue;
            }
            $candidatecohortids = $DB->get_fieldset_select(
                'tool_mucatalog_section_cohortvisible',
                'cohortid',
                "sectionid = ?",
                [$candidate->id],
                'cohortid ASC'
            );
            if (array_map('intval', $candidatecohortids) !== $cohortids) {
                continue;
            }
            $section = $candidate;
            break;
        }

        if (!$section) {
            $section = section::create((object)[
                'contextid' => $contextid,
                'name' => $name,
                'shortdescription' => $shortdescription,
                'guestvisible' => 0,
                'uservisible' => (int)$uservisible,
                'cohortvisible' => $cohortids,
                // Public programs and certifications were visible to all logged-in users,
                // there is nothing to review there. Cohort visibility should be reviewed before activation.
                'status' => ($uservisible ? util::STATUS_ACTIVE : util::STATUS_DRAFT),
            ]);
        }

        if ($DB->record_exists('tool_mucatalog_item', ['sectionid' => $section->id, 'type' => $type, 'referenceid' => $referenceid])) {
            return;
        }

        $classname::create((object)[
            'sectionid' => $section->id,
            'type' => $type,
            'referenceid' => $referenceid,
            'syncname' => 1,
            'status' => util::STATUS_ACTIVE,
        ]);
    }
}
