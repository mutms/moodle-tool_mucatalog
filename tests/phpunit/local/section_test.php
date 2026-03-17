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

use tool_mucatalog\local\section;
use tool_mucatalog\local\util;
use core\exception\invalid_parameter_exception;
use core\exception\coding_exception;
use core\exception\moodle_exception;

/**
 * Catalogue section test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\section
 */
final class section_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_defaults(): void {
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);

        $defaults = section::get_defaults(null);
        $this->assertObjectNotHasProperty('contextid', $defaults);
        $this->assertSame(null, $defaults->frontpagepriority);
        $this->assertSame('0', $defaults->guestvisible);
        $this->assertSame('1', $defaults->uservisible);
        $this->assertSame((string)util::STATUS_ACTIVE, $defaults->status);

        $defaults = section::get_defaults($categorycontext->id);
        $this->assertSame((string)$categorycontext->id, $defaults->contextid);
        $this->assertSame(null, $defaults->frontpagepriority);
        $this->assertSame('0', $defaults->guestvisible);
        $this->assertSame('1', $defaults->uservisible);
        $this->assertSame((string)util::STATUS_ACTIVE, $defaults->status);
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
        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
        ]);
        $this->assertSame('First section', $section->name);
        $this->assertSame('', $section->shortdescription);
        $this->assertSame((string)$syscontext->id, $section->contextid);
        $this->assertSame(null, $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_DRAFT, $section->status);
        $this->assertSame('0', $section->guestvisible);
        $this->assertSame('0', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertTimeCurrent($section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        $this->setCurrentTimeStart();
        $section = section::create((object)[
            'name' => 'Second section',
            'shortdescription' => 'Second desc',
            'contextid' => $categorycontext->id,
            'frontpagepriority' => '777',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $this->assertSame('Second section', $section->name);
        $this->assertSame('Second desc', $section->shortdescription);
        $this->assertSame('777', $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);
        $this->assertSame('1', $section->guestvisible);
        $this->assertSame('0', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertTimeCurrent($section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        $this->setCurrentTimeStart();
        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third section',
            'frontpageshow' => '0',
            'frontpagepriority' => '999',
            'uservisible' => 1,
        ]);
        $this->assertSame('Third section', $section->name);
        $this->assertSame('', $section->shortdescription);
        $this->assertSame((string)$syscontext->id, $section->contextid);
        $this->assertSame(null, $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_DRAFT, $section->status);
        $this->assertSame('0', $section->guestvisible);
        $this->assertSame('1', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertTimeCurrent($section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        try {
            section::create((object)[
                'contextid' => $syscontext->id,
                'name' => ' ',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (section name is required)', $ex->getMessage());
        }

        try {
            section::create((object)[
                'name' => 'Some',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (section contextid is required)', $ex->getMessage());
        }

        try {
            section::create((object)[
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

        $section0 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
        ]);

        $section = section::update((object)[
            'id' => $section0->id,
            'name' => 'Second section',
            'shortdescription' => 'Second desc',
            'frontpagepriority' => '777',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $this->assertSame('Second section', $section->name);
        $this->assertSame('Second desc', $section->shortdescription);
        $this->assertSame('777', $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);
        $this->assertSame('1', $section->guestvisible);
        $this->assertSame('0', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertSame($section0->timecreated, $section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        $section = section::update((object)[
            'id' => $section0->id,
            'name' => 'First section',
            'shortdescription' => 'First desc',
            'frontpagepriority' => '',
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [$cohort2->id, $cohort3->id],
        ]);
        $this->assertSame('First section', $section->name);
        $this->assertSame('First desc', $section->shortdescription);
        $this->assertSame(null, $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);
        $this->assertSame('0', $section->guestvisible);
        $this->assertSame('0', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertSame($section0->timecreated, $section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort2->id, $cohort3->id],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        $section = section::update((object)[
            'id' => $section0->id,
            'uservisible' => 1,
            'cohortvisible' => [$cohort2->id, $cohort3->id],
        ]);
        $this->assertSame('First section', $section->name);
        $this->assertSame('First desc', $section->shortdescription);
        $this->assertSame(null, $section->frontpagepriority);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);
        $this->assertSame('0', $section->guestvisible);
        $this->assertSame('1', $section->uservisible);
        $this->assertSame('0', $section->hiddenfromtenants);
        $this->assertSame($section0->timecreated, $section->timecreated);
        $this->assertTimeCurrent($section->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(section::get_cohortvisible_menu($section->id))
        );

        try {
            section::update((object)[
                'id' => $section0->id,
                'name' => ' ',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (section name is required)', $ex->getMessage());
        }

        try {
            section::update((object)[
                'id' => $section0->id,
                'contextid' => $categorycontext->id,
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (use move to change section context)', $ex->getMessage());
        }
    }

    public function test_move(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
        ]);

        $section = section::move($section->id, $categorycontext->id);
        $this->assertSame((string)$categorycontext->id, $section->contextid);

        $section = section::move($section->id, $syscontext->id);
        $this->assertSame((string)$syscontext->id, $section->contextid);

        try {
            section::move($section->id, $coursecontext->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (System or category context expected)', $ex->getMessage());
        }

        try {
            section::move($section->id, 0);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid context id specified context::instance_by_id()',
                $ex->getMessage()
            );
        }
    }

    public function test_is_activate_possible(): void {
        $syscontext = \context_system::instance();

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertTrue(section::is_activate_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertFalse(section::is_activate_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third section',
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertFalse(section::is_activate_possible($section));
    }

    public function test_activate(): void {
        $syscontext = \context_system::instance();

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
            'status' => util::STATUS_DRAFT,
        ]);

        $section = section::activate($section->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);

        $section = section::activate($section->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'status' => util::STATUS_ARCHIVED,
        ]);
        try {
            section::activate($section->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only draft sections can be activated)', $ex->getMessage());
        }
    }

    public function test_is_archive_possible(): void {
        $syscontext = \context_system::instance();

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertFalse(section::is_archive_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertTrue(section::is_archive_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third section',
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertFalse(section::is_archive_possible($section));
    }

    public function test_archive(): void {
        $syscontext = \context_system::instance();

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
            'status' => util::STATUS_ACTIVE,
        ]);

        $section = section::archive($section->id);
        $this->assertSame((string)util::STATUS_ARCHIVED, $section->status);

        $section = section::archive($section->id);
        $this->assertSame((string)util::STATUS_ARCHIVED, $section->status);

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'status' => util::STATUS_DRAFT,
        ]);
        try {
            section::archive($section->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only active sections can be archived)', $ex->getMessage());
        }
    }

    public function test_is_restore_possible(): void {
        $syscontext = \context_system::instance();

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertFalse(section::is_restore_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertFalse(section::is_restore_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third section',
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertTrue(section::is_restore_possible($section));
    }

    public function test_restore(): void {
        $syscontext = \context_system::instance();

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
            'status' => util::STATUS_ARCHIVED,
        ]);

        $section = section::restore($section->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);

        $section = section::restore($section->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $section->status);

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'status' => util::STATUS_DRAFT,
        ]);
        try {
            section::restore($section->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only archived sections can be restored)', $ex->getMessage());
        }
    }

    public function test_is_delete_possible(): void {
        $syscontext = \context_system::instance();

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertTrue(section::is_delete_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertFalse(section::is_delete_possible($section));

        $section = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third section',
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertTrue(section::is_delete_possible($section));
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

        section::delete($section1->id);

        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section3->id]));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_section', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section2->id, 'cohortid' => $cohort1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section2->id, 'cohortid' => $cohort2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section_cohortvisible', ['sectionid' => $section3->id, 'cohortid' => $cohort1->id]));
        $this->assertSame(3, $DB->count_records('tool_mucatalog_section_cohortvisible', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item2x1->id]));
        $this->assertSame(1, $DB->count_records('tool_mucatalog_item', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection', ['id' => $collection1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_collection', ['id' => $collection2->id]));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_collection', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_cohortvisible', ['collectionid' => $collection2->id, 'cohortid' => $cohort2->id]));
        $this->assertSame(1, $DB->count_records('tool_mucatalog_collection_cohortvisible', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection1->id, 'itemid' => $item2x1->id]));
        $this->assertSame(1, $DB->count_records('tool_mucatalog_collection_item', []));

        section::delete($section1->id);
    }

    public function test_get_cohortvisible_menu(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 2']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 3']);

        $section1 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
        ]);
        $menu = section::get_cohortvisible_menu($section1->id);
        $this->assertSame([], $menu);

        $section2 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $menu = section::get_cohortvisible_menu($section2->id);
        $this->assertSame([$cohort1->id => $cohort1->name, $cohort2->id => $cohort2->name], $menu);

        $section3 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Third section',
            'uservisible' => 0,
            'cohortvisible' => [$cohort2->id],
        ]);
        $menu = section::get_cohortvisible_menu($section3->id);
        $this->assertSame([$cohort2->id => $cohort2->name], $menu);

        $this->assertSame(3, $DB->count_records('tool_mucatalog_section_cohortvisible', []));
    }

    public function test_fix_mucatalog_active(): void {
        $syscontext = \context_system::instance();

        $this->assertSame(false, get_config('tool_mucatalog', 'active'));
        $this->assertSame(false, get_config('tool_mucatalog', 'guestvisible'));

        section::fix_mucatalog_active();

        $this->assertSame('0', get_config('tool_mucatalog', 'active'));
        $this->assertSame('0', get_config('tool_mucatalog', 'guestvisible'));

        $section1 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Section 1',
            'guestvisible' => 1,
            'status' => util::STATUS_DRAFT,
        ]);

        $this->assertSame('0', get_config('tool_mucatalog', 'active'));
        $this->assertSame('0', get_config('tool_mucatalog', 'guestvisible'));

        $section2 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Section 2',
            'guestvisible' => 1,
            'status' => util::STATUS_ARCHIVED,
        ]);

        $this->assertSame('0', get_config('tool_mucatalog', 'active'));
        $this->assertSame('0', get_config('tool_mucatalog', 'guestvisible'));

        $section3 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Section 3',
            'guestvisible' => 0,
            'status' => util::STATUS_ACTIVE,
        ]);

        $this->assertSame('1', get_config('tool_mucatalog', 'active'));
        $this->assertSame('0', get_config('tool_mucatalog', 'guestvisible'));

        $section4 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Section 4',
            'guestvisible' => 1,
            'status' => util::STATUS_ACTIVE,
        ]);

        $this->assertSame('1', get_config('tool_mucatalog', 'active'));
        $this->assertSame('1', get_config('tool_mucatalog', 'guestvisible'));

        $section4 = section::update((object)[
            'id' => $section4->id,
            'guestvisible' => 0,
        ]);

        $this->assertSame('1', get_config('tool_mucatalog', 'active'));
        $this->assertSame('0', get_config('tool_mucatalog', 'guestvisible'));

        $section4 = section::archive($section4->id);

        $this->assertSame('1', get_config('tool_mucatalog', 'active'));
        $this->assertSame('0', get_config('tool_mucatalog', 'guestvisible'));

        $section3 = section::archive($section3->id);

        $this->assertSame('0', get_config('tool_mucatalog', 'active'));
        $this->assertSame('0', get_config('tool_mucatalog', 'guestvisible'));
    }

    public function test_pre_course_category_delete(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);

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

        $section3 = section::create((object)[
            'contextid' => $categorycontext->id,
            'name' => 'Section 3',
            'status' => util::STATUS_ARCHIVED,
        ]);

        $section4 = section::create((object)[
            'contextid' => $categorycontext->id,
            'name' => 'Section 4',
            'status' => util::STATUS_DRAFT,
        ]);

        $category->delete_full();

        $section1 = $DB->get_record('tool_mucatalog_section', ['id' => $section1->id], '*', MUST_EXIST);
        $section2 = $DB->get_record('tool_mucatalog_section', ['id' => $section2->id], '*', MUST_EXIST);
        $section3 = $DB->get_record('tool_mucatalog_section', ['id' => $section3->id], '*', MUST_EXIST);
        $section4 = $DB->get_record('tool_mucatalog_section', ['id' => $section4->id]);

        $this->assertSame((string)util::STATUS_ACTIVE, $section1->status);
        $this->assertSame((string)util::STATUS_ARCHIVED, $section2->status);
        $this->assertSame((string)util::STATUS_ARCHIVED, $section3->status);
        $this->assertFalse($section4);
    }

    public function test_event_cohort_deleted(): void {
        $syscontext = \context_system::instance();
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 2']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 3']);

        $section1 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First section',
        ]);
        $menu = section::get_cohortvisible_menu($section1->id);
        $this->assertSame([], $menu);

        $section2 = section::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second section',
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $menu = section::get_cohortvisible_menu($section2->id);
        $this->assertSame([$cohort1->id => $cohort1->name, $cohort2->id => $cohort2->name], $menu);

        cohort_delete_cohort($cohort1);

        $menu = section::get_cohortvisible_menu($section1->id);
        $this->assertSame([], $menu);

        $menu = section::get_cohortvisible_menu($section2->id);
        $this->assertSame([$cohort2->id => $cohort2->name], $menu);
    }
}
