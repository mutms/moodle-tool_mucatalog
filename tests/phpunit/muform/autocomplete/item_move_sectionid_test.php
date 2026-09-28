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

namespace tool_mucatalog\phpunit\muform\autocomplete;

use tool_mucatalog\muform\autocomplete\item_move_sectionid;
use tool_mucatalog\local\util;
use tool_mulib\local\mulib;

/**
 * Item move section autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\muform\autocomplete\item_move_sectionid
 */
final class item_move_sectionid_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search_label(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();

        $section0 = $generator->create_section([
            'contextid' => $syscontext->id,
            'name' => 'Fancy section',
            'shortdescription' => 'Just a small description',
        ]);
        $section1 = $generator->create_section(['contextid' => $catcontext1->id, 'status' => util::STATUS_ARCHIVED]);
        $section2 = $generator->create_section(['contextid' => $catcontext2->id]);
        $section3 = $generator->create_section(['contextid' => $catcontext2->id, 'status' => util::STATUS_DRAFT]);

        $item0 = $generator->create_item([
            'sectionid' => $section0->id,
            'type' => 'course',
            'referenceid' => $course->id,
        ]);
        $item1 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course->id,
        ]);
        $item2 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course->id,
        ]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $catcontext2->id);

        $this->setUser($user1);

        $source = new item_move_sectionid((int)$item0->id);
        $this->assertSame([(int)$item0->id], $source->get_args());
        $expected = [
            (int)$section1->id => $section1->name,
            (int)$section2->id => $section2->name,
            (int)$section3->id => $section3->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertNull($source->search('', 2));

        $this->assertNull($source->label((string)$section0->id));
        $this->assertSame($section1->name, $source->label((string)$section1->id));
        $this->assertSame($section2->name, $source->label((string)$section2->id));
        $this->assertSame($section3->name, $source->label((string)$section3->id));
        $this->assertNull($source->label('0'));
        $this->assertNull($source->label('-1'));
        $this->assertNull($source->label('abc'));
        $this->assertNull($source->label((string)($section3->id + 100)));
        $this->assertNull($source->validate((string)$section1->id));

        $source = new item_move_sectionid((int)$item1->id);
        $this->assertSame([(int)$section0->id => $section0->name], $source->search('fancy', 50));
        $this->assertSame([(int)$section0->id => $section0->name], $source->search('small', 50));
        $this->assertSame([], $source->search('xyz', 50));
        $this->assertSame($section0->name, $source->label((string)$section0->id));
        $this->assertNull($source->label((string)$section1->id));

        $this->setUser($user2);

        $source = new item_move_sectionid((int)$item2->id);
        $this->assertSame([(int)$section3->id => $section3->name], $source->search('', 50));
        $this->assertNull($source->label((string)$section0->id));
        $this->assertNull($source->label((string)$section1->id));
        $this->assertNull($source->label((string)$section2->id));
        $this->assertSame($section3->name, $source->label((string)$section3->id));

        try {
            new item_move_sectionid((int)$item1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Manage Universal catalogue).',
                $ex->getMessage()
            );
        }
    }

    public function test_search_tenant(): void {
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $course = $this->getDataGenerator()->create_course();

        $section0 = $generator->create_section(['contextid' => $syscontext->id]);
        $section1 = $generator->create_section(['contextid' => $tenant1catcontext->id]);
        $section2 = $generator->create_section(['contextid' => $tenant2catcontext->id]);

        $item0 = $generator->create_item(['sectionid' => $section0->id, 'type' => 'course', 'referenceid' => $course->id]);
        $item1 = $generator->create_item(['sectionid' => $section1->id, 'type' => 'course', 'referenceid' => $course->id]);

        $this->setAdminUser();

        $source = new item_move_sectionid((int)$item0->id);
        $expected = [
            (int)$section1->id => $section1->name,
            (int)$section2->id => $section2->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertNull($source->validate((string)$section1->id));
        $this->assertNull($source->validate((string)$section2->id));

        $source = new item_move_sectionid((int)$item1->id);
        $this->assertSame([(int)$section0->id => $section0->name], $source->search('', 50));
        $this->assertNull($source->validate((string)$section0->id));
        $this->assertSame('Error', $source->validate((string)$section2->id));
    }
}
