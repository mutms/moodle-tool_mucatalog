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

use tool_mucatalog\local\collection;
use tool_mucatalog\local\section;
use tool_mucatalog\local\util;
use core\exception\invalid_parameter_exception;
use core\exception\coding_exception;
use core\exception\moodle_exception;

/**
 * Catalogue collection test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\collection
 */
final class collection_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_defaults(): void {
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);

        $defaults = collection::get_defaults(null);
        $this->assertObjectNotHasProperty('contextid', $defaults);
        $this->assertSame(null, $defaults->frontpagepriority);
        $this->assertSame('0', $defaults->guestvisible);
        $this->assertSame('1', $defaults->uservisible);

        $defaults = collection::get_defaults($categorycontext->id);
        $this->assertSame((string)$categorycontext->id, $defaults->contextid);
        $this->assertSame(null, $defaults->frontpagepriority);
        $this->assertSame('0', $defaults->guestvisible);
        $this->assertSame('1', $defaults->uservisible);
    }

    public function test_create(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $this->setCurrentTimeStart();
        $collection = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First collection',
        ]);
        $this->assertSame('First collection', $collection->name);
        $this->assertSame('', $collection->shortdescription);
        $this->assertSame((string)$syscontext->id, $collection->contextid);
        $this->assertSame(null, $collection->frontpagepriority);
        $this->assertSame('0', $collection->guestvisible);
        $this->assertSame('0', $collection->uservisible);
        $this->assertSame('0', $collection->hiddenfromtenants);
        $this->assertTimeCurrent($collection->timecreated);
        $this->assertTimeCurrent($collection->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(collection::get_cohortvisible_menu($collection->id))
        );

        $this->setCurrentTimeStart();
        $collection = collection::create((object)[
            'name' => 'Second collection',
            'shortdescription' => 'Second desc',
            'contextid' => $categorycontext->id,
            'frontpagepriority' => '777',
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $this->assertSame('Second collection', $collection->name);
        $this->assertSame('Second desc', $collection->shortdescription);
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

        $this->setCurrentTimeStart();
        $collection = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third collection',
            'uservisible' => 1,
        ]);
        $this->assertSame('Third collection', $collection->name);
        $this->assertSame('', $collection->shortdescription);
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

        try {
            collection::create((object)[
                'contextid' => $syscontext->id,
                'name' => ' ',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (collection name is required)', $ex->getMessage());
        }

        try {
            collection::create((object)[
                'name' => 'Some',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (collection contextid is required)', $ex->getMessage());
        }

        try {
            collection::create((object)[
                'contextid' => $coursecontext->id,
                'name' => 'Xyz',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (System or category context expected)', $ex->getMessage());
        }
    }

    public function test_update(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $collection0 = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First collection',
        ]);

        $collection = collection::update((object)[
            'id' => $collection0->id,
            'name' => 'Second collection',
            'shortdescription' => 'Second desc',
            'frontpagepriority' => '777',
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $this->assertSame('Second collection', $collection->name);
        $this->assertSame('Second desc', $collection->shortdescription);
        $this->assertSame('777', $collection->frontpagepriority);
        $this->assertSame('1', $collection->guestvisible);
        $this->assertSame('0', $collection->uservisible);
        $this->assertSame('0', $collection->hiddenfromtenants);
        $this->assertSame($collection0->timecreated, $collection->timecreated);
        $this->assertTimeCurrent($collection->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(collection::get_cohortvisible_menu($collection->id))
        );

        $collection = collection::update((object)[
            'id' => $collection0->id,
            'name' => 'First collection',
            'shortdescription' => 'First desc',
            'frontpagepriority' => '',
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [$cohort2->id, $cohort3->id],
        ]);
        $this->assertSame('First collection', $collection->name);
        $this->assertSame('First desc', $collection->shortdescription);
        $this->assertSame(null, $collection->frontpagepriority);
        $this->assertSame('0', $collection->guestvisible);
        $this->assertSame('0', $collection->uservisible);
        $this->assertSame('0', $collection->hiddenfromtenants);
        $this->assertSame($collection0->timecreated, $collection->timecreated);
        $this->assertTimeCurrent($collection->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort2->id, $cohort3->id],
            array_keys(collection::get_cohortvisible_menu($collection->id))
        );

        $collection = collection::update((object)[
            'id' => $collection0->id,
            'uservisible' => 1,
            'cohortvisible' => [$cohort2->id, $cohort3->id],
        ]);
        $this->assertSame('First collection', $collection->name);
        $this->assertSame('First desc', $collection->shortdescription);
        $this->assertSame(null, $collection->frontpagepriority);
        $this->assertSame('0', $collection->guestvisible);
        $this->assertSame('1', $collection->uservisible);
        $this->assertSame('0', $collection->hiddenfromtenants);
        $this->assertSame($collection0->timecreated, $collection->timecreated);
        $this->assertTimeCurrent($collection->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(collection::get_cohortvisible_menu($collection->id))
        );

        try {
            collection::update((object)[
                'id' => $collection0->id,
                'name' => ' ',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (collection name is required)', $ex->getMessage());
        }

        try {
            collection::update((object)[
                'id' => $collection0->id,
                'contextid' => $categorycontext->id,
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (use move to change collection context)', $ex->getMessage());
        }
    }

    public function test_move(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $collection = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First collection',
        ]);

        $collection = collection::move($collection->id, $categorycontext->id);
        $this->assertSame((string)$categorycontext->id, $collection->contextid);

        $collection = collection::move($collection->id, $syscontext->id);
        $this->assertSame((string)$syscontext->id, $collection->contextid);

        try {
            collection::move($collection->id, $coursecontext->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (System or category context expected)', $ex->getMessage());
        }

        try {
            collection::move($collection->id, 0);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid context id specified context::instance_by_id()',
                $ex->getMessage()
            );
        }
    }

    public function test_delete(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $section1 = $generator->create_section([
            'status' => util::STATUS_ACTIVE,
        ]);
        $item1x1 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);
        $item1x2 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course2->id,
        ]);

        $section2 = $generator->create_section([
            'contextid' => $categorycontext->id,
            'status' => util::STATUS_ACTIVE,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $item2x1 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);

        $section3 = $generator->create_section([
            'status' => util::STATUS_ARCHIVED,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);

        $collection1 = $generator->create_collection();
        $ci1x1x1 = $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item1x1->id]);
        $ci1x2x1 = $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item2x1->id]);

        $collection2 = $generator->create_collection([
            'uservisible' => 0,
            'cohortvisible' => [$cohort2->id],
        ]);
        $ci2x1x1 = $generator->create_collection_item(['collectionid' => $collection2->id, 'itemid' => $item1x1->id]);

        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section3->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_section', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section2->id, 'cohortid' => $cohort1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section2->id, 'cohortid' => $cohort2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section3->id, 'cohortid' => $cohort1->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_section_cohortvisible', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item1x1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item1x2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item2x1->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_item', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection', ['id' => $collection1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_collection', ['id' => $collection2->id]));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_collection', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_cohortvisible', ['collectionid' => $collection2->id, 'cohortid' => $cohort2->id]));
        $this->assertSame(1, $DB->count_records('tool_mucatalog_collection_cohortvisible', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection1->id, 'itemid' => $item1x1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection1->id, 'itemid' => $item2x1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection2->id, 'itemid' => $item1x1->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_collection_item', []));

        collection::delete($collection2->id);

        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section3->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_section', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section2->id, 'cohortid' => $cohort1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section2->id, 'cohortid' => $cohort2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section3->id, 'cohortid' => $cohort1->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_section_cohortvisible', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item1x1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item1x2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item2x1->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_item', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection', ['id' => $collection1->id]));
        $this->assertSame(1, $DB->count_records('tool_mucatalog_collection', []));

        $this->assertSame(0, $DB->count_records('tool_mucatalog_collection_cohortvisible', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection1->id, 'itemid' => $item1x1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection1->id, 'itemid' => $item2x1->id]));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_collection_item', []));
    }

    public function test_get_cohortvisible_menu(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 2']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 3']);

        $collection1 = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First collection',
        ]);
        $menu = collection::get_cohortvisible_menu($collection1->id);
        $this->assertSame([], $menu);

        $collection2 = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second collection',
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $menu = collection::get_cohortvisible_menu($collection2->id);
        $this->assertSame([$cohort1->id => $cohort1->name, $cohort2->id => $cohort2->name], $menu);

        $collection3 = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third collection',
            'uservisible' => 0,
            'cohortvisible' => [$cohort2->id],
        ]);
        $menu = collection::get_cohortvisible_menu($collection3->id);
        $this->assertSame([$cohort2->id => $cohort2->name], $menu);

        $this->assertSame(3, $DB->count_records('tool_mucatalog_collection_cohortvisible', []));
    }

    public function test_pre_course_category_delete(): void {
        global $DB;

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $section1 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Section 1',
            'status' => util::STATUS_ACTIVE,
        ]);

        $section2 = section::create((object)[
            'contextid' => $categorycontext->id,
            'name' => 'Section 2',
            'status' => util::STATUS_ACTIVE,
        ]);

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

        $collection1 = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Collection 1',
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);
        $collection2 = collection::create((object)[
            'contextid' => $categorycontext->id,
            'name' => 'Collection 2',
            'uservisible' => 0,
            'cohortvisible' => [$cohort2->id],
        ]);

        $ci1x1 = $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item1->id]);
        $ci1x2 = $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item2->id]);
        $ci2x1 = $generator->create_collection_item(['collectionid' => $collection2->id, 'itemid' => $item1->id]);
        $ci2x2 = $generator->create_collection_item(['collectionid' => $collection2->id, 'itemid' => $item2->id]);

        $category->delete_full();

        $section1 = $DB->get_record('tool_mucatalog_section', ['id' => $section1->id], '*', MUST_EXIST);
        $section2 = $DB->get_record('tool_mucatalog_section', ['id' => $section2->id], '*', MUST_EXIST);
        $collection1 = $DB->get_record('tool_mucatalog_collection', ['id' => $collection1->id], '*', MUST_EXIST);
        $collection2 = $DB->get_record('tool_mucatalog_collection', ['id' => $collection2->id]);

        $this->assertSame((string)util::STATUS_ACTIVE, $section1->status);
        $this->assertSame((string)util::STATUS_ARCHIVED, $section2->status);
        $this->assertFalse($collection2);
    }

    public function test_event_cohort_deleted(): void {
        $syscontext = \context_system::instance();
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 2']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 3']);

        $collection1 = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First collection',
        ]);
        $menu = collection::get_cohortvisible_menu($collection1->id);
        $this->assertSame([], $menu);

        $collection2 = collection::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second collection',
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $menu = collection::get_cohortvisible_menu($collection2->id);
        $this->assertSame([$cohort1->id => $cohort1->name, $cohort2->id => $cohort2->name], $menu);

        cohort_delete_cohort($cohort1);

        $menu = collection::get_cohortvisible_menu($collection1->id);
        $this->assertSame([], $menu);

        $menu = collection::get_cohortvisible_menu($collection2->id);
        $this->assertSame([$cohort2->id => $cohort2->name], $menu);
    }
}
