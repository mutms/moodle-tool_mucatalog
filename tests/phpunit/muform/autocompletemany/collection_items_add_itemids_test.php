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

namespace tool_mucatalog\phpunit\muform\autocompletemany;

use tool_mucatalog\muform\autocompletemany\collection_items_add_itemids;
use tool_mucatalog\local\util;
use tool_mulib\local\mulib;

/**
 * Collection items autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\muform\autocompletemany\collection_items_add_itemids
 */
final class collection_items_add_itemids_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search_labels(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course0 = $this->getDataGenerator()->create_course();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $section0 = $generator->create_section(['contextid' => $syscontext->id]);
        $section1 = $generator->create_section(['contextid' => $catcontext1->id]);
        $section2 = $generator->create_section(['contextid' => $catcontext2->id]);
        $section3 = $generator->create_section(['contextid' => $syscontext->id, 'status' => util::STATUS_ARCHIVED]);
        $section4 = $generator->create_section(['contextid' => $syscontext->id, 'status' => util::STATUS_DRAFT]);

        $item0x0 = $generator->create_item([
            'sectionid' => $section0->id,
            'type' => 'course',
            'name' => 'Fancy name',
            'referenceid' => $course0->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item0x1 = $generator->create_item([
            'sectionid' => $section0->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item0x2 = $generator->create_item([
            'sectionid' => $section0->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item1x1 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item2x1 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item3x1 = $generator->create_item([
            'sectionid' => $section3->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item4x1 = $generator->create_item([
            'sectionid' => $section4->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $catcontext2->id);

        $collection0 = $generator->create_collection(['contextid' => $syscontext->id]);
        $collection1 = $generator->create_collection(['contextid' => $catcontext1->id]);
        $collection2 = $generator->create_collection(['contextid' => $catcontext2->id]);

        $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item0x0->id]);
        $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item2x1->id]);

        $label0x0 = "$item0x0->name | Course | $section0->name";
        $label1x1 = "$item1x1->name | Course | $section1->name";
        $label2x1 = "$item2x1->name | Course | $section2->name";

        $all = [
            (string)$item0x0->id, (string)$item0x1->id, (string)$item0x2->id, (string)$item1x1->id,
            (string)$item2x1->id, (string)$item3x1->id, (string)$item4x1->id,
        ];

        $this->setUser($user1);

        $source = new collection_items_add_itemids((int)$collection0->id);
        $this->assertSame([(int)$collection0->id], $source->get_args());
        $expected = [
            (int)$item0x0->id => $label0x0,
            (int)$item1x1->id => $label1x1,
            (int)$item2x1->id => $label2x1,
        ];
        $this->assertSame($expected, $source->search('', 50, []));
        $this->assertNull($source->search('', 2, []));
        $this->assertSame(
            [(int)$item0x0->id => $label0x0, (int)$item2x1->id => $label2x1],
            $source->search('', 50, [(string)$item1x1->id])
        );
        $this->assertSame([(int)$item0x0->id => $label0x0], $source->search('fancy', 50, []));
        $this->assertSame($expected, $source->labels($all));
        $this->assertSame([], $source->validate($all));

        // Unknown and invalid values.
        $this->assertSame([], $source->labels(['0', '-1', 'abc', '', (string)($item4x1->id + 100)]));

        // Items already in the collection are excluded.
        $source = new collection_items_add_itemids((int)$collection1->id);
        $this->assertSame([(int)$item1x1->id => $label1x1], $source->search('', 50, []));
        $this->assertSame([(int)$item1x1->id => $label1x1], $source->labels($all));
        $this->assertSame([], $source->validate($all));

        $this->setUser($user2);

        $source = new collection_items_add_itemids((int)$collection2->id);
        $this->assertSame([(int)$item2x1->id => $label2x1], $source->search('', 50, []));
        $this->assertSame([(int)$item2x1->id => $label2x1], $source->labels($all));
        $this->assertSame([], $source->validate($all));

        try {
            new collection_items_add_itemids((int)$collection1->id);
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
        $item2 = $generator->create_item(['sectionid' => $section2->id, 'type' => 'course', 'referenceid' => $course->id]);
        $all = [(string)$item0->id, (string)$item1->id, (string)$item2->id];

        $collection0 = $generator->create_collection(['contextid' => $syscontext->id]);
        $collection1 = $generator->create_collection(['contextid' => $tenant1catcontext->id]);

        $this->setAdminUser();

        $source = new collection_items_add_itemids((int)$collection0->id);
        $this->assertSame([(int)$item0->id, (int)$item1->id, (int)$item2->id], array_keys($source->search('', 50, [])));
        $this->assertSame([], $source->validate($all));

        $source = new collection_items_add_itemids((int)$collection1->id);
        $this->assertSame([(int)$item0->id, (int)$item1->id], array_keys($source->search('', 50, [])));
        $this->assertSame([(string)$item2->id => 'Error'], $source->validate($all));
    }
}
