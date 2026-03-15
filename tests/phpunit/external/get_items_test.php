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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_mucatalog\phpunit\external;

use tool_mucatalog\external\get_items;
use tool_mucatalog\local\util;

/**
 * Ajax web service for browsing tests.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\external\get_items
 */
final class get_items_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_definition(): void {
        $function = \core_external\external_api::external_function_info('tool_mucatalog_get_items');
        $this->assertSame(get_items::class, $function->classname);
        $this->assertSame('execute', $function->methodname);
        $this->assertSame('tool_mucatalog', $function->component);
        $this->assertSame(true, $function->allowed_from_ajax);
        $this->assertSame('read', $function->type);
        $this->assertSame(false, $function->loginrequired); // JS call will require login, this not-logged-in access.
    }

    public function test_execute(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);
        cohort_add_member($cohort3->id, $user3->id);

        $section1 = $generator->create_section([
            'name' => 'Section 1',
            'frontpagepriority' => '-100',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);

        $section2 = $generator->create_section([
            'name' => 'Section 2',
            'frontpagepriority' => null,
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);

        $section3 = $generator->create_section([
            'name' => 'Section 3',
            'frontpagepriority' => null,
            'status' => util::STATUS_ARCHIVED,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);

        $section4 = $generator->create_section([
            'name' => 'Section 4',
            'frontpagepriority' => null,
            'status' => util::STATUS_DRAFT,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);

        $section5 = $generator->create_section([
            'name' => 'Section 5',
            'frontpagepriority' => '300',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);

        $collection1 = $generator->create_collection([
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [],
        ]);

        $collection2 = $generator->create_collection([
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [],
        ]);

        $item1 = $generator->create_item(['sectionid' => $section1->id, 'name' => 'Item 1', 'type' => 'course', 'referenceid' => $course1->id]);
        $item1b = $generator->create_item(['sectionid' => $section1->id, 'name' => 'Item 1b', 'type' => 'course', 'referenceid' => $course2->id]);
        $item1c = $generator->create_item(['sectionid' => $section1->id, 'name' => 'Item 1c', 'type' => 'course', 'referenceid' => $course3->id, 'status' => util::STATUS_DRAFT]);
        $item1d = $generator->create_item(['sectionid' => $section1->id, 'name' => 'Item 1d', 'type' => 'course', 'referenceid' => $course4->id, 'status' => util::STATUS_ARCHIVED]);
        $item2 = $generator->create_item(['sectionid' => $section2->id, 'name' => 'Item 2', 'type' => 'course', 'referenceid' => $course1->id]);
        $item3 = $generator->create_item(['sectionid' => $section3->id, 'name' => 'Item 3', 'type' => 'course', 'referenceid' => $course1->id]);
        $item3b = $generator->create_item(['sectionid' => $section3->id, 'name' => 'Item 3b', 'type' => 'course', 'referenceid' => $course2->id]);
        $item4 = $generator->create_item(['sectionid' => $section4->id, 'name' => 'Item 4', 'type' => 'course', 'referenceid' => $course1->id]);
        $item5 = $generator->create_item(['sectionid' => $section5->id, 'name' => 'Item 5', 'type' => 'course', 'referenceid' => $course1->id]);

        $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item1->id]);
        $generator->create_collection_item(['collectionid' => $collection2->id, 'itemid' => $item2->id]);

        $this->assertTrue(has_capability('tool/mucatalog:browse', $syscontext));
        $this->assertTrue(has_capability('tool/mucatalog:browse', $syscontext, $guest->id));
        $this->assertTrue(has_capability('tool/mucatalog:browse', $syscontext, $user1->id));

        $this->setUser(null);

        $result = get_items::execute(0, 'name', 0, 10, []);
        $result = get_items::clean_returnvalue(get_items::execute_returns(), $result);
        $this->assertCount(3, $result['items']);
        $this->assertSame(10, $result['nextlimitfrom']);
        $this->assertSame(0, $result['hasmore']);
        $item = $result['items'][0];
        $this->assertSame((int)$item1->id, $item['itemid']);
        $this->assertSame('Course', $item['itemtype']);
        $this->assertSame($item1->name, $item['itemname']);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucatalog/item.php?id=$item1->id",
            $item['itemurl']
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/1/tool_mucatalog/item_image/$item1->id/geopattern.svg",
            $item['imageurl']
        );
        $this->assertSame(false, $item['registered']);

        $item = $result['items'][1];
        $this->assertSame((int)$item1b->id, $item['itemid']);
        $this->assertSame('Course', $item['itemtype']);
        $this->assertSame($item1b->name, $item['itemname']);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucatalog/item.php?id=$item1b->id",
            $item['itemurl']
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/1/tool_mucatalog/item_image/$item1b->id/geopattern.svg",
            $item['imageurl']
        );
        $this->assertSame(false, $item['registered']);

        $item = $result['items'][2];
        $this->assertSame((int)$item2->id, $item['itemid']);
        $this->assertSame('Course', $item['itemtype']);
        $this->assertSame($item2->name, $item['itemname']);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucatalog/item.php?id=$item2->id",
            $item['itemurl']
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/1/tool_mucatalog/item_image/$item2->id/geopattern.svg",
            $item['imageurl']
        );
        $this->assertSame(false, $item['registered']);

        $result = get_items::execute(0, 'name', 0, 10, [['field' => 'type', 'value' => 'course']]);
        $result = get_items::clean_returnvalue(get_items::execute_returns(), $result);
        $this->assertCount(3, $result['items']);
        $this->assertSame(10, $result['nextlimitfrom']);

        $result = get_items::execute(0, 'name', 0, 10, [['field' => 'search', 'value' => 'Item 1b']]);
        $result = get_items::clean_returnvalue(get_items::execute_returns(), $result);
        $this->assertCount(1, $result['items']);
        $this->assertSame(10, $result['nextlimitfrom']);
        $this->assertSame(0, $result['hasmore']);
        $item = $result['items'][0];
        $this->assertSame((int)$item1b->id, $item['itemid']);
        $this->assertSame('Course', $item['itemtype']);
        $this->assertSame($item1b->name, $item['itemname']);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucatalog/item.php?id=$item1b->id",
            $item['itemurl']
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/1/tool_mucatalog/item_image/$item1b->id/geopattern.svg",
            $item['imageurl']
        );
        $this->assertSame(false, $item['registered']);

        $this->setUser($user1);

        $result = get_items::execute(0, 'name', 0, 10, []);
        $result = get_items::clean_returnvalue(get_items::execute_returns(), $result);
        $this->assertCount(1, $result['items']);
        $this->assertSame(10, $result['nextlimitfrom']);
        $this->assertSame(0, $result['hasmore']);
        $item = $result['items'][0];
        $this->assertSame((int)$item2->id, $item['itemid']);
        $this->assertSame('Course', $item['itemtype']);
        $this->assertSame($item2->name, $item['itemname']);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucatalog/item.php?id=$item2->id",
            $item['itemurl']
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/1/tool_mucatalog/item_image/$item2->id/geopattern.svg",
            $item['imageurl']
        );
        $this->assertSame(false, $item['registered']);

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');

        $result = get_items::execute(0, 'name', 0, 10, []);
        $result = get_items::clean_returnvalue(get_items::execute_returns(), $result);
        $this->assertCount(1, $result['items']);
        $this->assertSame(10, $result['nextlimitfrom']);
        $this->assertSame(0, $result['hasmore']);
        $item = $result['items'][0];
        $this->assertSame((int)$item2->id, $item['itemid']);
        $this->assertSame('Course', $item['itemtype']);
        $this->assertSame($item2->name, $item['itemname']);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucatalog/item.php?id=$item2->id",
            $item['itemurl']
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/1/tool_mucatalog/item_image/$item2->id/geopattern.svg",
            $item['imageurl']
        );
        $this->assertSame(true, $item['registered']);
    }
}
