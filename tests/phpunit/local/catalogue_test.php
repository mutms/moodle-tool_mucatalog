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

use tool_mucatalog\local\catalogue;
use tool_mucatalog\local\util;

/**
 * Universal catalogue browser test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\catalogue
 */
final class catalogue_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_visible_sections(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

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

        $section6 = $generator->create_section([
            'name' => 'Section 5',
            'frontpagepriority' => '999',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [],
        ]);

        $sections = catalogue::get_visible_sections(0, true, null);
        $this->assertEquals($section1, $sections[$section1->id]);
        $this->assertCount(1, $sections);

        $sections = catalogue::get_visible_sections(0, false, null);
        $this->assertEquals($section1, $sections[$section1->id]);
        $this->assertEquals($section2, $sections[$section2->id]);
        $this->assertCount(2, $sections);

        $this->assertTrue(catalogue::is_section_visible($section1->id, 0, null));
        $this->assertTrue(catalogue::is_section_visible($section2->id, 0, null));
        $this->assertFalse(catalogue::is_section_visible($section3->id, 0, null));
        $this->assertFalse(catalogue::is_section_visible($section4->id, 0, null));
        $this->assertFalse(catalogue::is_section_visible($section5->id, 0, null));
        $this->assertFalse(catalogue::is_section_visible($section6->id, 0, null));

        $sections = catalogue::get_visible_sections($guest->id, true, null);
        $this->assertEquals($section1, $sections[$section1->id]);
        $this->assertCount(1, $sections);

        $sections = catalogue::get_visible_sections($guest->id, false, null);
        $this->assertEquals($section1, $sections[$section1->id]);
        $this->assertEquals($section2, $sections[$section2->id]);
        $this->assertSame([(int)$section1->id, (int)$section2->id], array_keys($sections));

        $this->assertTrue(catalogue::is_section_visible($section1->id, $guest->id, null));
        $this->assertTrue(catalogue::is_section_visible($section2->id, $guest->id, null));
        $this->assertFalse(catalogue::is_section_visible($section3->id, $guest->id, null));
        $this->assertFalse(catalogue::is_section_visible($section4->id, $guest->id, null));
        $this->assertFalse(catalogue::is_section_visible($section5->id, $guest->id, null));
        $this->assertFalse(catalogue::is_section_visible($section6->id, $guest->id, null));

        $sections = catalogue::get_visible_sections($user1->id, true, null);
        $this->assertCount(0, $sections);

        $sections = catalogue::get_visible_sections($user1->id, false, null);
        $this->assertSame([(int)$section2->id], array_keys($sections));

        $this->assertFalse(catalogue::is_section_visible($section1, $user1->id, null));
        $this->assertTrue(catalogue::is_section_visible($section2, $user1->id, null));
        $this->assertFalse(catalogue::is_section_visible($section3->id, $user1->id, null));
        $this->assertFalse(catalogue::is_section_visible($section4->id, $user1->id, null));
        $this->assertFalse(catalogue::is_section_visible($section5, $user1->id, null));
        $this->assertFalse(catalogue::is_section_visible($section6, $user1->id, null));

        $sections = catalogue::get_visible_sections($user2->id, true, null);
        $this->assertSame([(int)$section1->id, (int)$section5->id], array_keys($sections));

        $sections = catalogue::get_visible_sections($user2->id, false, null);
        $this->assertSame([(int)$section1->id, (int)$section2->id, (int)$section5->id], array_keys($sections));

        $this->assertTrue(catalogue::is_section_visible($section1->id, $user2->id, null));
        $this->assertTrue(catalogue::is_section_visible($section2, $user2->id, null));
        $this->assertFalse(catalogue::is_section_visible($section3, $user2->id, null));
        $this->assertFalse(catalogue::is_section_visible($section4, $user2->id, null));
        $this->assertTrue(catalogue::is_section_visible($section5->id, $user2->id, null));
        $this->assertFalse(catalogue::is_section_visible($section6->id, $user2->id, null));

        $sections = catalogue::get_visible_sections($user3->id, true, null);
        $this->assertSame([(int)$section1->id, (int)$section5->id], array_keys($sections));

        $sections = catalogue::get_visible_sections($user3->id, false, null);
        $this->assertSame([(int)$section1->id, (int)$section2->id, (int)$section5->id], array_keys($sections));
    }

    public function test_get_visible_collections(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);
        cohort_add_member($cohort3->id, $user3->id);

        $collection1 = $generator->create_collection([
            'name' => 'Collection 1',
            'frontpagepriority' => '-100',
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);

        $collection2 = $generator->create_collection([
            'name' => 'Collection 2',
            'frontpagepriority' => null,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);

        $collection3 = $generator->create_collection([
            'name' => 'Collection 3',
            'frontpagepriority' => '300',
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);

        $collection4 = $generator->create_collection([
            'name' => 'Collection 4',
            'frontpagepriority' => '999',
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [],
        ]);

        $collections = catalogue::get_visible_collections(0, true, null);
        $this->assertEquals($collection1, $collections[$collection1->id]);
        $this->assertCount(1, $collections);

        $collections = catalogue::get_visible_collections(0, false, null);
        $this->assertEquals($collection1, $collections[$collection1->id]);
        $this->assertEquals($collection2, $collections[$collection2->id]);
        $this->assertCount(2, $collections);

        $this->assertTrue(catalogue::is_collection_visible($collection1->id, 0, null));
        $this->assertTrue(catalogue::is_collection_visible($collection2->id, 0, null));
        $this->assertFalse(catalogue::is_collection_visible($collection3->id, 0, null));
        $this->assertFalse(catalogue::is_collection_visible($collection4->id, 0, null));

        $collections = catalogue::get_visible_collections($guest->id, true, null);
        $this->assertEquals($collection1, $collections[$collection1->id]);
        $this->assertCount(1, $collections);

        $collections = catalogue::get_visible_collections($guest->id, false, null);
        $this->assertEquals($collection1, $collections[$collection1->id]);
        $this->assertEquals($collection2, $collections[$collection2->id]);
        $this->assertSame([(int)$collection1->id, (int)$collection2->id], array_keys($collections));

        $this->assertTrue(catalogue::is_collection_visible($collection1->id, $guest->id, null));
        $this->assertTrue(catalogue::is_collection_visible($collection2->id, $guest->id, null));
        $this->assertFalse(catalogue::is_collection_visible($collection3->id, $guest->id, null));
        $this->assertFalse(catalogue::is_collection_visible($collection4->id, $guest->id, null));

        $collections = catalogue::get_visible_collections($user1->id, true, null);
        $this->assertCount(0, $collections);

        $collections = catalogue::get_visible_collections($user1->id, false, null);
        $this->assertSame([(int)$collection2->id], array_keys($collections));

        $this->assertFalse(catalogue::is_collection_visible($collection1, $user1->id, null));
        $this->assertTrue(catalogue::is_collection_visible($collection2, $user1->id, null));
        $this->assertFalse(catalogue::is_collection_visible($collection3->id, $user1->id, null));
        $this->assertFalse(catalogue::is_collection_visible($collection4->id, $user1->id, null));

        $collections = catalogue::get_visible_collections($user2->id, true, null);
        $this->assertSame([(int)$collection1->id, (int)$collection3->id], array_keys($collections));

        $collections = catalogue::get_visible_collections($user2->id, false, null);
        $this->assertSame([(int)$collection1->id, (int)$collection2->id, (int)$collection3->id], array_keys($collections));

        $this->assertTrue(catalogue::is_collection_visible($collection1->id, $user2->id, null));
        $this->assertTrue(catalogue::is_collection_visible($collection2->id, $user2->id, null));
        $this->assertTrue(catalogue::is_collection_visible($collection3, $user2->id, null));
        $this->assertFalse(catalogue::is_collection_visible($collection4, $user2->id, null));

        $collections = catalogue::get_visible_collections($user3->id, true, null);
        $this->assertSame([(int)$collection1->id, (int)$collection3->id], array_keys($collections));

        $collections = catalogue::get_visible_collections($user3->id, false, null);
        $this->assertSame([(int)$collection1->id, (int)$collection2->id, (int)$collection3->id], array_keys($collections));
    }

    public function test_get_visible_items(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

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

        // Not-logged-in user.

        $items = catalogue::get_visible_items(0, 0, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item1->id, (int)$item1b->id, (int)$item2->id], array_keys($items));
        $item1->sectionname = $section1->name;
        $this->assertEquals($item1, $items[$item1->id]);

        $items = catalogue::get_visible_items(0, 0, null, 'name', 1, 10, []);
        $this->assertSame([(int)$item1b->id, (int)$item2->id], array_keys($items));

        $items = catalogue::get_visible_items(0, 0, null, 'name', 0, 2, []);
        $this->assertSame([(int)$item1->id, (int)$item1b->id], array_keys($items));

        $items = catalogue::get_visible_items($section1->id, 0, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item1->id, (int)$item1b->id], array_keys($items));

        $items = catalogue::get_visible_items($section2->id, 0, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item2->id], array_keys($items));

        $items = catalogue::get_visible_items($section3->id, 0, null, 'name', 0, 10, []);
        $this->assertSame([], array_keys($items));

        $items = catalogue::get_visible_items(-1 * $collection1->id, 0, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item1->id], array_keys($items));

        $items = catalogue::get_visible_items(-1 * $collection2->id, 0, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item2->id], array_keys($items));

        $this->assertTrue(catalogue::is_item_visible($item1, $section1, 0, null));
        $this->assertTrue(catalogue::is_item_visible($item1b->id, $section1->id, 0, null));
        $this->assertFalse(catalogue::is_item_visible($item1c, $section1->id, 0, null));
        $this->assertFalse(catalogue::is_item_visible($item1d->id, $section1->id, 0, null));
        $this->assertTrue(catalogue::is_item_visible($item2->id, $section2, 0, null));
        $this->assertFalse(catalogue::is_item_visible($item3, $section3->id, 0, null));
        $this->assertFalse(catalogue::is_item_visible($item3b->id, $section3, 0, null));
        $this->assertFalse(catalogue::is_item_visible($item4, $section4, 0, null));
        $this->assertFalse(catalogue::is_item_visible($item5, $section5, 0, null));

        // Guest.

        $items = catalogue::get_visible_items(0, $guest->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item1->id, (int)$item1b->id, (int)$item2->id], array_keys($items));

        $this->assertTrue(catalogue::is_item_visible($item1->id, $section1->id, $guest->id, null));
        $this->assertTrue(catalogue::is_item_visible($item1b, $section1, $guest->id, null));
        $this->assertFalse(catalogue::is_item_visible($item1c->id, $section1, $guest->id, null));
        $this->assertTrue(catalogue::is_item_visible($item2, $section2->id, $guest->id, null));
        $this->assertFalse(catalogue::is_item_visible($item3->id, $section3, $guest->id, null));
        $this->assertFalse(catalogue::is_item_visible($item3b, $section3->id, $guest->id, null));
        $this->assertFalse(catalogue::is_item_visible($item4->id, $section4->id, $guest->id, null));
        $this->assertFalse(catalogue::is_item_visible($item5->id, $section5->id, $guest->id, null));

        // Real users.

        $items = catalogue::get_visible_items(0, $user1->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item2->id], array_keys($items));

        $this->assertFalse(catalogue::is_item_visible($item1, $section1, $user1->id, null));
        $this->assertFalse(catalogue::is_item_visible($item1b, $section1, $user1->id, null));
        $this->assertFalse(catalogue::is_item_visible($item1c, $section1, $user1->id, null));
        $this->assertTrue(catalogue::is_item_visible($item2, $section2, $user1->id, null));
        $this->assertFalse(catalogue::is_item_visible($item3, $section3, $user1->id, null));
        $this->assertFalse(catalogue::is_item_visible($item3b, $section3, $user1->id, null));
        $this->assertFalse(catalogue::is_item_visible($item4, $section4, $user1->id, null));
        $this->assertFalse(catalogue::is_item_visible($item5, $section5, $user1->id, null));

        $items = catalogue::get_visible_items(0, $user2->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item1->id, (int)$item1b->id, (int)$item2->id, (int)$item5->id], array_keys($items));

        $this->assertTrue(catalogue::is_item_visible($item1->id, $section1->id, $user2->id, null));
        $this->assertTrue(catalogue::is_item_visible($item1b->id, $section1->id, $user2->id, null));
        $this->assertFalse(catalogue::is_item_visible($item1c->id, $section1->id, $user2->id, null));
        $this->assertTrue(catalogue::is_item_visible($item2->id, $section2->id, $user2->id, null));
        $this->assertFalse(catalogue::is_item_visible($item3->id, $section3->id, $user2->id, null));
        $this->assertFalse(catalogue::is_item_visible($item3b->id, $section3->id, $user2->id, null));
        $this->assertFalse(catalogue::is_item_visible($item4->id, $section4->id, $user2->id, null));
        $this->assertTrue(catalogue::is_item_visible($item5->id, $section5->id, $user2->id, null));

        $items = catalogue::get_visible_items($section1->id, $user2->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item1->id, (int)$item1b->id], array_keys($items));

        $items = catalogue::get_visible_items(-1 * $collection1->id, $user2->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item1->id], array_keys($items));

        $items = catalogue::get_visible_items(0, $user2->id, null, 'name', 0, 10, [['field' => 'type', 'value' => 'course']]);
        $this->assertSame([(int)$item1->id, (int)$item1b->id, (int)$item2->id, (int)$item5->id], array_keys($items));

        $items = catalogue::get_visible_items(0, $user2->id, null, 'name', 0, 10, [['field' => 'type', 'value' => 'program']]);
        $this->assertSame([], array_keys($items));

        // Time restrictions.

        $now = time();

        $items = catalogue::get_visible_items(0, $user1->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item2->id], array_keys($items));
        $this->assertTrue(catalogue::is_item_visible($item2, $section2, $user1->id, null));

        $item2 = \tool_mucatalog\local\item\course::update((object)[
            'id' => $item2->id,
            'hiddenbefore' => $now + 10,
        ]);
        $items = catalogue::get_visible_items(0, $user1->id, null, 'name', 0, 10, []);
        $this->assertSame([], array_keys($items));
        $this->assertFalse(catalogue::is_item_visible($item2, $section2, $user1->id, null));

        $item2 = \tool_mucatalog\local\item\course::update((object)[
            'id' => $item2->id,
            'hiddenbefore' => $now - 10,
        ]);
        $items = catalogue::get_visible_items(0, $user1->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item2->id], array_keys($items));
        $this->assertTrue(catalogue::is_item_visible($item2, $section2, $user1->id, null));

        $item2 = \tool_mucatalog\local\item\course::update((object)[
            'id' => $item2->id,
            'hiddenafter' => $now - 5,
        ]);
        $items = catalogue::get_visible_items(0, $user1->id, null, 'name', 0, 10, []);
        $this->assertSame([], array_keys($items));
        $this->assertFalse(catalogue::is_item_visible($item2, $section2, $user1->id, null));

        $item2 = \tool_mucatalog\local\item\course::update((object)[
            'id' => $item2->id,
            'hiddenafter' => $now + 5,
        ]);
        $items = catalogue::get_visible_items(0, $user1->id, null, 'name', 0, 10, []);
        $this->assertSame([(int)$item2->id], array_keys($items));
        $this->assertTrue(catalogue::is_item_visible($item2, $section2, $user1->id, null));
    }

    public function test_format_short_description(): void {
        $this->assertSame(
            "<p>test <em>bold</em></p>\n\n<p>next <em>itealic</em>em&gt; alert('xss')</p>\n",
            catalogue::format_short_description("test *bold*\n\nnext <em>itealic</em>em> <javascript>alert('xss')</javascript>")
        );
    }
}
