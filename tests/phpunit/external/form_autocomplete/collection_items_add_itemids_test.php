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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_mucatalog\phpunit\external\form_autocomplete;

use tool_mucatalog\external\form_autocomplete\collection_items_add_itemids;
use tool_mucatalog\local\util;
use core\exception\moodle_exception;

/**
 * Autocomplete WS for collection itemids tests.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\external\form_autocomplete\collection_items_add_itemids
 */
final class collection_items_add_itemids_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

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

        $c1x0x0 = $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item0x0->id]);
        $c1x2x1 = $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item2x1->id]);

        $this->setUser($user1);

        $result = collection_items_add_itemids::execute('', $collection0->id);
        $result = collection_items_add_itemids::clean_returnvalue(collection_items_add_itemids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(3, $result['list']);
        $item = $result['list'][0];
        $this->assertSame((int)$item0x0->id, $item['value']);
        $this->assertSame("$item0x0->name | Course | $section0->name", $item['label']);
        $item = $result['list'][1];
        $this->assertSame((int)$item1x1->id, $item['value']);
        $this->assertSame("$item1x1->name | Course | $section1->name", $item['label']);
        $item = $result['list'][2];
        $this->assertSame((int)$item2x1->id, $item['value']);
        $this->assertSame("$item2x1->name | Course | $section2->name", $item['label']);

        $this->assertNull(collection_items_add_itemids::validate_value($item0x0->id, ['collectionid' => $collection0->id], $syscontext));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x1->id, ['collectionid' => $collection0->id], $syscontext));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x2->id, ['collectionid' => $collection0->id], $syscontext));
        $this->assertNull(collection_items_add_itemids::validate_value($item1x1->id, ['collectionid' => $collection0->id], $syscontext));
        $this->assertNull(collection_items_add_itemids::validate_value($item2x1->id, ['collectionid' => $collection0->id], $syscontext));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item3x1->id, ['collectionid' => $collection0->id], $syscontext));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item4x1->id, ['collectionid' => $collection0->id], $syscontext));

        $result = collection_items_add_itemids::execute('', $collection1->id);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $item = $result['list'][0];
        $this->assertSame($item1x1->id, $item['value']);
        $this->assertSame("$item1x1->name | Course | $section1->name", $item['label']);

        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x0->id, ['collectionid' => $collection1->id], $catcontext1));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x1->id, ['collectionid' => $collection1->id], $catcontext1));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x2->id, ['collectionid' => $collection1->id], $catcontext1));
        $this->assertNull(collection_items_add_itemids::validate_value($item1x1->id, ['collectionid' => $collection1->id], $catcontext1));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item2x1->id, ['collectionid' => $collection1->id], $catcontext1));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item3x1->id, ['collectionid' => $collection1->id], $catcontext1));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item4x1->id, ['collectionid' => $collection1->id], $catcontext1));

        $result = collection_items_add_itemids::execute('fancy', $collection0->id);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $item = $result['list'][0];
        $this->assertSame($item0x0->id, $item['value']);
        $this->assertSame("$item0x0->name | Course | $section0->name", $item['label']);

        $this->setUser($user2);

        $result = collection_items_add_itemids::execute('', $collection2->id);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $item = $result['list'][0];
        $this->assertSame($item2x1->id, $item['value']);
        $this->assertSame("$item2x1->name | Course | $section2->name", $item['label']);

        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x0->id, ['collectionid' => $collection2->id], $catcontext2));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x1->id, ['collectionid' => $collection2->id], $catcontext2));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item0x2->id, ['collectionid' => $collection2->id], $catcontext2));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item1x1->id, ['collectionid' => $collection2->id], $catcontext2));
        $this->assertNull(collection_items_add_itemids::validate_value($item2x1->id, ['collectionid' => $collection2->id], $catcontext2));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item3x1->id, ['collectionid' => $collection2->id], $catcontext2));
        $this->assertSame('Error', collection_items_add_itemids::validate_value($item4x1->id, ['collectionid' => $collection2->id], $catcontext2));

        try {
            collection_items_add_itemids::execute('', $collection1->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Manage Universal catalogue).',
                $ex->getMessage()
            );
        }
    }
}
