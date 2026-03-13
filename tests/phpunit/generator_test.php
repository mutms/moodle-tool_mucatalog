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

namespace tool_mucatalog\phpunit;

use tool_mucatalog\local\util;
use tool_mucatalog\local\section;
use tool_mucatalog\local\collection;

/**
 * Catalogue generator test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog_generator
 */
final class generator_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create_section(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $this->setCurrentTimeStart();
        $section = $generator->create_section();
        $this->assertSame('Section 1', $section->name);
        $this->assertSame((string)$syscontext->id, $section->contextid);
        $this->assertSame(null, $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);
        $this->assertSame('0', $section->guestvisible);
        $this->assertSame('1', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertTimeCurrent($section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        $section = $generator->create_section([
            'name' => 'Some section',
            'contextid' => $categorycontext->id,
            'frontpagepriority' => '777',
            'status' => util::STATUS_DRAFT,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $this->assertSame('Some section', $section->name);
        $this->assertSame('777', $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_DRAFT, $section->status);
        $this->assertSame('1', $section->guestvisible);
        $this->assertSame('0', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertTimeCurrent($section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        $section = $generator->create_section([]);
        $this->assertSame('0', $section->hiddenfromtenants);

        $section = $generator->create_section([
            'hiddenfromtenants' => 1,
        ]);
        $this->assertSame('1', $section->hiddenfromtenants);
    }

    public function test_create_item(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $this->setCurrentTimeStart();
        $item = $generator->create_item([
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);
        $this->assertSame($section->id, $item->sectionid);
        $this->assertSame('course', $item->type);
        $this->assertSame($course1->id, $item->referenceid);
        $this->assertSame('1', $item->syncname);
        $this->assertSame($course1->fullname, $item->name);
        $this->assertSame(null, $item->hiddenbefore);
        $this->assertSame(null, $item->hiddenafter);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);
        $this->assertTimeCurrent($item->timecreated);
        $this->assertTimeCurrent($item->timemodified);

        $now = time();

        $this->setCurrentTimeStart();
        $item = $generator->create_item([
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'name' => 'Fancy name',
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertSame($section->id, $item->sectionid);
        $this->assertSame('course', $item->type);
        $this->assertSame($course2->id, $item->referenceid);
        $this->assertSame('0', $item->syncname);
        $this->assertSame('Fancy name', $item->name);
        $this->assertSame((string)($now - DAYSECS), $item->hiddenbefore);
        $this->assertSame((string)($now + DAYSECS), $item->hiddenafter);
        $this->assertSame((string)util::STATUS_DRAFT, $item->status);
        $this->assertTimeCurrent($item->timecreated);
        $this->assertTimeCurrent($item->timemodified);

        $item = $generator->create_item([
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course3->id,
            'syncname' => '1',
            'name' => 'Ignored name',
        ]);
        $this->assertSame('course', $item->type);
        $this->assertSame($course3->id, $item->referenceid);
        $this->assertSame('1', $item->syncname);
        $this->assertSame($course3->fullname, $item->name);
    }

    public function test_create_collection(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $this->setCurrentTimeStart();
        $collection = $generator->create_collection();
        $this->assertSame('Collection 1', $collection->name);
        $this->assertSame((string)$syscontext->id, $collection->contextid);
        $this->assertSame(null, $collection->frontpagepriority);
        $this->assertSame('0', $collection->guestvisible);
        $this->assertSame('1', $collection->uservisible);
        $this->assertSame('0', $collection->hiddenfromtenants);
        $this->assertTimeCurrent($collection->timecreated);
        $this->assertTimeCurrent($collection->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(collection::get_cohortvisible_menu($collection->id))
        );

        $collection = $generator->create_collection([
            'name' => 'Some collection',
            'contextid' => $categorycontext->id,
            'frontpagepriority' => '777',
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $this->assertSame('Some collection', $collection->name);
        $this->assertSame('777', $collection->frontpagepriority);
        $this->assertSame('1', $collection->guestvisible);
        $this->assertSame('0', $collection->uservisible);
        $this->assertSame('0', $collection->hiddenfromtenants);
        $this->assertTimeCurrent($collection->timecreated);
        $this->assertTimeCurrent($collection->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(collection::get_cohortvisible_menu($collection->id))
        );

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        $collection = $generator->create_collection([]);
        $this->assertSame('0', $collection->hiddenfromtenants);

        $collection = $generator->create_collection([
            'hiddenfromtenants' => 1,
        ]);
        $this->assertSame('1', $collection->hiddenfromtenants);
    }

    public function test_create_collection_item(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section();

        $item1 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);
        $item2 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course2->id,
        ]);
        $item3 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);

        $collection1 = $generator->create_collection([]);
        $collection2 = $generator->create_collection([]);

        $ci1 = $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item1->id]);
        $this->assertSame($collection1->id, $ci1->collectionid);
        $this->assertSame($item1->id, $ci1->itemid);

        $ci2 = $generator->create_collection_item(['collectionid' => $collection2->id, 'itemid' => $item1->id]);
        $this->assertSame($collection2->id, $ci2->collectionid);
        $this->assertSame($item1->id, $ci2->itemid);
    }
}
