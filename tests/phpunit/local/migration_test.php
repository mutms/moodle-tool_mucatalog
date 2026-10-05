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
// phpcs:disable moodle.Files.LineLength.MaxExceeded

namespace tool_mucatalog\phpunit\local;

use tool_mucatalog\local\migration;
use tool_mucatalog\local\util;

/**
 * Legacy catalogue migration API test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\migration
 */
final class migration_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_add_migrated_item_programs(): void {
        global $DB;

        if (!\tool_mulib\local\mulib::is_muprog_available()) {
            $this->markTestSkipped('tool_muprog not available');
        }

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 2']);

        $program1 = $programgenerator->create_program(['fullname' => 'Program 1']);
        $program2 = $programgenerator->create_program(['fullname' => 'Program 2']);
        $program3 = $programgenerator->create_program(['fullname' => 'Program 3', 'contextid' => $catcontext1->id]);
        $program4 = $programgenerator->create_program(['fullname' => 'Program 4']);
        $program5 = $programgenerator->create_program(['fullname' => 'Program 5']);
        $program6 = $programgenerator->create_program(['fullname' => 'Program 6']);
        $program7 = $programgenerator->create_program(['fullname' => 'Program 7']);

        $this->assertFalse(\tool_mulib\local\mulib::is_mucatalog_active());

        // Not visible to anybody.
        migration::add_migrated_item('program', $program7->id, $syscontext->id, false, []);
        migration::add_migrated_item('program', $program7->id, $syscontext->id, false, [-1]);
        $this->assertSame(0, $DB->count_records('tool_mucatalog_section', []));
        $this->assertSame(0, $DB->count_records('tool_mucatalog_item', []));

        // Cohort visibility is migrated as draft section.
        migration::add_migrated_item('program', $program4->id, $syscontext->id, false, [$cohort2->id, $cohort1->id]);
        $sections = $DB->get_records('tool_mucatalog_section', [], 'id ASC');
        $this->assertCount(1, $sections);
        $section1 = reset($sections);
        $this->assertSame('Programs: Cohort 1, Cohort 2', $section1->name);
        $this->assertSame('Section created during migration from the old program catalogue.', $section1->shortdescription);
        $this->assertSame((string)$syscontext->id, $section1->contextid);
        $this->assertSame((string)util::STATUS_DRAFT, $section1->status);
        $this->assertSame('0', $section1->guestvisible);
        $this->assertSame('0', $section1->uservisible);
        $this->assertSame('0', $section1->hiddenfromtenants);
        $this->assertNull($section1->frontpagepriority);
        $this->assertEquals(
            [$cohort1->id, $cohort2->id],
            $DB->get_fieldset_select('tool_mucatalog_section_cohortvisible', 'cohortid', 'sectionid = ?', [$section1->id], 'cohortid ASC')
        );
        $items = $DB->get_records('tool_mucatalog_item', ['sectionid' => $section1->id]);
        $this->assertCount(1, $items);
        $item = reset($items);
        $this->assertSame('program', $item->type);
        $this->assertSame((string)$program4->id, $item->referenceid);
        $this->assertSame('1', $item->syncname);
        $this->assertSame('Program 4', $item->name);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);
        $this->assertNull($item->hiddenbefore);
        $this->assertNull($item->hiddenafter);
        $this->assertFalse(\tool_mulib\local\mulib::is_mucatalog_active());

        // Same cohorts in different order and with invalid cohorts use the same section.
        migration::add_migrated_item('program', $program5->id, $syscontext->id, false, [$cohort1->id, $cohort2->id, -1, $cohort1->id]);
        $this->assertSame(1, $DB->count_records('tool_mucatalog_section', []));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_item', ['sectionid' => $section1->id]));

        // Repeated call does not create duplicates.
        migration::add_migrated_item('program', $program5->id, $syscontext->id, false, [$cohort1->id, $cohort2->id]);
        $this->assertSame(1, $DB->count_records('tool_mucatalog_section', []));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_item', ['sectionid' => $section1->id]));

        // Different cohorts mean different section.
        migration::add_migrated_item('program', $program6->id, $syscontext->id, false, [$cohort1->id]);
        $this->assertSame(2, $DB->count_records('tool_mucatalog_section', []));
        $section2 = $DB->get_record('tool_mucatalog_section', ['name' => 'Programs: Cohort 1'], '*', MUST_EXIST);
        $this->assertSame((string)util::STATUS_DRAFT, $section2->status);
        $this->assertEquals(
            [$cohort1->id],
            $DB->get_fieldset_select('tool_mucatalog_section_cohortvisible', 'cohortid', 'sectionid = ?', [$section2->id], 'cohortid ASC')
        );
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['sectionid' => $section2->id, 'type' => 'program', 'referenceid' => $program6->id]));

        // Public visibility is migrated as active section, cohorts are ignored.
        migration::add_migrated_item('program', $program1->id, $syscontext->id, true, [$cohort1->id]);
        migration::add_migrated_item('program', $program2->id, $syscontext->id, true, []);
        $this->assertSame(3, $DB->count_records('tool_mucatalog_section', []));
        $section3 = $DB->get_record('tool_mucatalog_section', ['name' => 'Programs: public'], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $section3->contextid);
        $this->assertSame((string)util::STATUS_ACTIVE, $section3->status);
        $this->assertSame('0', $section3->guestvisible);
        $this->assertSame('1', $section3->uservisible);
        $this->assertSame(0, $DB->count_records('tool_mucatalog_section_cohortvisible', ['sectionid' => $section3->id]));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_item', ['sectionid' => $section3->id]));
        $this->assertTrue(\tool_mulib\local\mulib::is_mucatalog_active());
        $this->assertSame('0', get_config('tool_mucatalog', 'hasguestsection'));

        // Different context means different section.
        migration::add_migrated_item('program', $program3->id, $catcontext1->id, true, []);
        $this->assertSame(4, $DB->count_records('tool_mucatalog_section', []));
        $section4 = $DB->get_record('tool_mucatalog_section', ['name' => 'Programs: public', 'contextid' => $catcontext1->id], '*', MUST_EXIST);
        $this->assertSame((string)util::STATUS_ACTIVE, $section4->status);
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['sectionid' => $section4->id, 'type' => 'program', 'referenceid' => $program3->id]));

        // Sections that were modified after migration are not reused.
        $DB->set_field('tool_mucatalog_section', 'name', 'Renamed', ['id' => $section3->id]);
        migration::add_migrated_item('program', $program7->id, $syscontext->id, true, []);
        $this->assertSame(5, $DB->count_records('tool_mucatalog_section', []));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_item', ['sectionid' => $section3->id]));
    }

    public function test_add_migrated_item_certifications(): void {
        global $DB;

        if (!\tool_mulib\local\mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $syscontext = \context_system::instance();
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);

        $program1 = $programgenerator->create_program(['fullname' => 'Program 1']);
        $certification1 = $certificationgenerator->create_certification(['fullname' => 'Certification 1']);
        $certification2 = $certificationgenerator->create_certification(['fullname' => 'Certification 2']);

        migration::add_migrated_item('certification', $certification1->id, $syscontext->id, true, []);
        migration::add_migrated_item('certification', $certification2->id, $syscontext->id, false, [$cohort1->id]);
        // Programs and certifications never share sections.
        migration::add_migrated_item('program', $program1->id, $syscontext->id, true, []);

        $this->assertSame(3, $DB->count_records('tool_mucatalog_section', []));

        $section1 = $DB->get_record('tool_mucatalog_section', ['name' => 'Certifications: public'], '*', MUST_EXIST);
        $this->assertSame('Section created during migration from the old certification catalogue.', $section1->shortdescription);
        $this->assertSame((string)util::STATUS_ACTIVE, $section1->status);
        $this->assertSame('1', $section1->uservisible);
        $items = $DB->get_records('tool_mucatalog_item', ['sectionid' => $section1->id]);
        $this->assertCount(1, $items);
        $item = reset($items);
        $this->assertSame('certification', $item->type);
        $this->assertSame((string)$certification1->id, $item->referenceid);
        $this->assertSame('Certification 1', $item->name);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $section2 = $DB->get_record('tool_mucatalog_section', ['name' => 'Certifications: Cohort 1'], '*', MUST_EXIST);
        $this->assertSame((string)util::STATUS_DRAFT, $section2->status);
        $this->assertSame('0', $section2->uservisible);
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['sectionid' => $section2->id, 'type' => 'certification', 'referenceid' => $certification2->id]));

        $section3 = $DB->get_record('tool_mucatalog_section', ['name' => 'Programs: public'], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['sectionid' => $section3->id, 'type' => 'program', 'referenceid' => $program1->id]));
    }

    public function test_add_migrated_item_invalid(): void {
        $syscontext = \context_system::instance();
        $course = $this->getDataGenerator()->create_course();

        try {
            migration::add_migrated_item('course', $course->id, $syscontext->id, true, []);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Invalid legacy catalogue item type', $ex->getMessage());
        }
    }
}
