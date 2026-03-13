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
 * Catalogue behat generators.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_mucatalog_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'sections' => [
                'singular' => 'section',
                'datagenerator' => 'section',
                'required' => ['name'],
                'switchids' => [
                    'status' => 'status',
                ],
            ],
            'items' => [
                'singular' => 'item',
                'datagenerator' => 'item',
                'required' => ['section', 'type', 'reference'],
                'switchids' => [
                    'status' => 'status',
                    'section' => 'sectionid',
                ],
            ],
            'collections' => [
                'singular' => 'collection',
                'datagenerator' => 'collection',
                'required' => ['name'],
            ],
            'collection_items' => [
                'singular' => 'collection_item',
                'datagenerator' => 'collection_item',
                'required' => ['collection', 'item'],
                'switchids' => [
                    'collection' => 'collectionid',
                    'item' => 'itemid',
                ],
            ],
        ];
    }

    /**
     * Look up the status constant.
     *
     * @param string $status
     * @return int
     */
    protected function get_status_id(string $status): int {
        $status = strtolower($status);
        switch ($status) {
            case 'draft':
                return \tool_mucatalog\local\util::STATUS_DRAFT;
            case 'active':
                return \tool_mucatalog\local\util::STATUS_ACTIVE;
            case 'archived':
                return \tool_mucatalog\local\util::STATUS_ARCHIVED;
            default:
                throw new \Exception('Invalid status "' . $status . '"');
        }
    }

    /**
     * Look up section id.
     *
     * @param string $sectionname
     * @return int
     */
    protected function get_section_id(string $sectionname): int {
        global $DB;

        $id = $DB->get_field('tool_mucatalog_section', 'id', ['name' => $sectionname]);

        if (!$id) {
            throw new Exception('The specified section with name "' . $sectionname . '" does not exist');
        }

        return $id;
    }

    /**
     * Look up collection id.
     *
     * @param string $collectionname
     * @return int
     */
    protected function get_collection_id(string $collectionname): int {
        global $DB;

        $id = $DB->get_field('tool_mucatalog_collection', 'id', ['name' => $collectionname]);

        if (!$id) {
            throw new Exception('The specified collection with name "' . $collectionname . '" does not exist');
        }

        return $id;
    }

    /**
     * Look up item id.
     *
     * @param string $itemname
     * @return int
     */
    protected function get_item_id(string $itemname): int {
        global $DB;

        $id = $DB->get_field('tool_mucatalog_item', 'id', ['name' => $itemname]);

        if (!$id) {
            throw new Exception('The specified item with name "' . $itemname . '" does not exist');
        }

        return $id;
    }

    /**
     * Pre-process section, populate contextid property.
     *
     * @param array $section
     * @return array
     */
    protected function preprocess_section(array $section): array {
        if (!empty($section['contextlevel'])) {
            $section['contextid'] = $this->get_context($section['contextlevel'], $section['reference'])->id;
            unset($section['contextlevel'], $section['reference']);
        }

        if (!empty($section['cohortvisible'])) {
            $cohortids = [];
            $cohortidnumbers = explode(',', $section['cohortvisible']);
            foreach ($cohortidnumbers as $cohortidnumber) {
                $cohortidnumber = trim($cohortidnumber);
                if ($cohortidnumber === '') {
                    continue;
                }
                $cohortids[] = $this->get_cohort_id($cohortidnumber);
            }
            $section['cohortvisible'] = $cohortids;
        }

        return $section;
    }

    /**
     * Pre-process collection, populate contextid property.
     *
     * @param array $collection
     * @return array
     */
    protected function preprocess_collection(array $collection): array {
        if (!empty($collection['contextlevel'])) {
            $collection['contextid'] = $this->get_context($collection['contextlevel'], $collection['reference'])->id;
            unset($collection['contextlevel'], $collection['reference']);
        }

        if (!empty($collection['cohortvisible'])) {
            $cohortids = [];
            $cohortidnumbers = explode(',', $collection['cohortvisible']);
            foreach ($cohortidnumbers as $cohortidnumber) {
                $cohortidnumber = trim($cohortidnumber);
                if ($cohortidnumber === '') {
                    continue;
                }
                $cohortids[] = $this->get_cohort_id($cohortidnumber);
            }
            $collection['cohortvisible'] = $cohortids;
        }

        return $collection;
    }

    /**
     * Pre-process item, populate referenceid property.
     *
     * @param array $item
     * @return array
     */
    protected function preprocess_item(array $item): array {
        global $DB;

        if ($item['type'] === 'course') {
            $reference = $DB->get_record('course', ['fullname' => $item['reference']], '*', MUST_EXIST);
        } else if ($item['type'] === 'program') {
            $reference = $DB->get_record('tool_muprog_program', ['fullname' => $item['reference']], '*', MUST_EXIST);
        } else if ($item['type'] === 'certification') {
            $reference = $DB->get_record('tool_mucertify_certification', ['fullname' => $item['reference']], '*', MUST_EXIST);
        } else {
            throw new \Exception('Unknown item type');
        }
        unset($item['reference']);
        $item['referenceid'] = $reference->id;

        return $item;
    }
}
