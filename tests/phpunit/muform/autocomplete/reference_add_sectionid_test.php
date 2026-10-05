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
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mucatalog\phpunit\muform\autocomplete;

use tool_mucatalog\muform\autocomplete\reference_add_sectionid;
use tool_mucatalog\local\util;
use tool_mulib\local\mulib;

/**
 * Reference add section autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\muform\autocomplete\reference_add_sectionid
 */
final class reference_add_sectionid_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search_label(): void {
        if (!mulib::is_muprog_available()) {
            $this->markTestSkipped('tool_muprog not available');
        }

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $programgenerator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $programgenerator->create_program(['contextid' => $catcontext2->id]);

        $section0 = $generator->create_section([
            'contextid' => $syscontext->id,
            'name' => 'Fancy section',
            'shortdescription' => 'Just a small description',
        ]);
        $section1 = $generator->create_section(['contextid' => $catcontext1->id, 'status' => util::STATUS_ARCHIVED]);
        $section2 = $generator->create_section(['contextid' => $catcontext2->id]);
        $section3 = $generator->create_section(['contextid' => $catcontext2->id, 'status' => util::STATUS_DRAFT]);

        $generator->create_item(['sectionid' => $section2->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        $adderroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:addprogram', CAP_ALLOW, $adderroleid, $syscontext);
        // User 1 may manage all sections and add all programs.
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($adderroleid, $user1->id, $syscontext->id);
        // User 2 may manage sections in category 2 and add programs from category 1.
        role_assign($editorroleid, $user2->id, $catcontext2->id);
        role_assign($adderroleid, $user2->id, $catcontext1->id);
        // User 3 may manage all sections, but cannot add any programs.
        role_assign($editorroleid, $user3->id, $syscontext->id);

        $this->setUser($user1);

        // Archived sections and sections with the program already are not offered.
        $source = new reference_add_sectionid('program', (int)$program1->id);
        $this->assertSame(['program', (int)$program1->id], $source->get_args());
        $expected = [
            (int)$section0->id => $section0->name,
            (int)$section3->id => $section3->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertNull($source->search('', 1));
        $this->assertSame([(int)$section0->id => $section0->name], $source->search('fancy', 50));
        $this->assertSame([(int)$section0->id => $section0->name], $source->search('small', 50));
        $this->assertSame([], $source->search('xyz', 50));

        $this->assertSame($section0->name, $source->label((string)$section0->id));
        $this->assertNull($source->label((string)$section1->id));
        $this->assertNull($source->label((string)$section2->id));
        $this->assertSame($section3->name, $source->label((string)$section3->id));
        $this->assertNull($source->label('0'));
        $this->assertNull($source->label('-1'));
        $this->assertNull($source->label('abc'));
        $this->assertNull($source->label((string)($section3->id + 100)));
        $this->assertNull($source->validate((string)$section0->id));

        $source = new reference_add_sectionid('program', (int)$program2->id);
        $expected = [
            (int)$section0->id => $section0->name,
            (int)$section2->id => $section2->name,
            (int)$section3->id => $section3->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertSame($section2->name, $source->label((string)$section2->id));

        $this->setUser($user2);

        // Only sections user may manage are offered.
        $source = new reference_add_sectionid('program', (int)$program1->id);
        $this->assertSame([(int)$section3->id => $section3->name], $source->search('', 50));
        $this->assertNull($source->label((string)$section0->id));
        $this->assertNull($source->label((string)$section2->id));
        $this->assertSame($section3->name, $source->label((string)$section3->id));

        try {
            new reference_add_sectionid('program', (int)$program2->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add program to catalogue sections).',
                $ex->getMessage()
            );
        }

        $this->setUser($user3);

        try {
            new reference_add_sectionid('program', (int)$program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }

        $this->setUser($user1);

        try {
            new reference_add_sectionid('program', (int)$program2->id + 100);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
        }
        try {
            new reference_add_sectionid('xyz', (int)$program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
        }
    }

    public function test_search_other_types(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $section0 = $generator->create_section([]);
        $section1 = $generator->create_section([]);
        $course = $this->getDataGenerator()->create_course();
        $generator->create_item(['sectionid' => $section1->id, 'type' => 'course', 'referenceid' => $course->id]);

        $this->setAdminUser();

        $source = new reference_add_sectionid('course', (int)$course->id);
        $this->assertSame([(int)$section0->id => $section0->name], $source->search('', 50));

        if (!mulib::is_mucertify_available()) {
            return;
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        $certification = $certificationgenerator->create_certification([]);
        $generator->create_item(['sectionid' => $section0->id, 'type' => 'certification', 'referenceid' => $certification->id]);

        $source = new reference_add_sectionid('certification', (int)$certification->id);
        $this->assertSame([(int)$section1->id => $section1->name], $source->search('', 50));
    }

    public function test_search_tenant(): void {
        if (!mulib::is_mutenancy_available() || !mulib::is_muprog_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $programgenerator->create_program(['contextid' => $syscontext->id]);
        $program1 = $programgenerator->create_program(['contextid' => $tenant1catcontext->id]);

        $section0 = $generator->create_section(['contextid' => $syscontext->id]);
        $section1 = $generator->create_section(['contextid' => $tenant1catcontext->id]);
        $section2 = $generator->create_section(['contextid' => $tenant2catcontext->id]);

        $this->setAdminUser();

        // Programs without tenant may be added to any section.
        $source = new reference_add_sectionid('program', (int)$program0->id);
        $expected = [
            (int)$section0->id => $section0->name,
            (int)$section1->id => $section1->name,
            (int)$section2->id => $section2->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertNull($source->validate((string)$section2->id));

        // Tenant programs may be added to sections of the same tenant and to sections without tenant.
        $source = new reference_add_sectionid('program', (int)$program1->id);
        $expected = [
            (int)$section0->id => $section0->name,
            (int)$section1->id => $section1->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertNull($source->validate((string)$section0->id));
        $this->assertNull($source->validate((string)$section1->id));
        $this->assertSame('Error', $source->validate((string)$section2->id));
    }
}
