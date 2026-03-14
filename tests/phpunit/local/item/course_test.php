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

namespace tool_mucatalog\phpunit\local\item;

use tool_mucatalog\local\util;
use tool_mucatalog\local\item;
use tool_mucatalog\local\item\course;
use core\exception\invalid_parameter_exception;
use core\exception\coding_exception;
use core\exception\moodle_exception;

/**
 * Course item test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\item\course
 */
final class course_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type_classname(): void {
        $this->assertSame(course::class, item::get_type_classname('course'));
    }

    public function test_get_type(): void {
        $this->assertSame('course', course::get_type());
        $this->assertSame('course', course::TYPE);
    }

    public function test_is_available(): void {
        $this->assertTrue(course::is_available());
    }

    public function test_get_create_form_class(): void {
        $this->assertSame(\tool_mucatalog\local\form\items_create::class, course::get_create_form_class());
    }

    public function test_get_create_form_referenceids_class(): void {
        $this->assertSame(
            \tool_mucatalog\external\form_autocomplete\items_create_courseids::class,
            course::get_create_form_referenceids_class()
        );
    }

    public function test_get_type_name(): void {
        $this->assertSame('Course', course::get_type_name());
    }

    public function test_create(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $this->setCurrentTimeStart();
        $item = course::create((object)[
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
        $this->assertSame((string)util::STATUS_DRAFT, $item->status);
        $this->assertTimeCurrent($item->timecreated);
        $this->assertTimeCurrent($item->timemodified);

        $now = time();

        $this->setCurrentTimeStart();
        $item = course::create((object)[
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

        $item = course::create((object)[
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

    public function test_create_multiple(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $items = course::create_multiple((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceids' => [$course1->id, $course2->id],
            'status' => util::STATUS_ACTIVE,
        ]);
        $item1 = array_values($items)[0];
        $item2 = array_values($items)[1];

        $this->assertSame($course1->id, $item1->referenceid);
        $this->assertSame($course1->fullname, $item1->name);
        $this->assertSame('course', $item1->type);
        $this->assertSame((string)util::STATUS_ACTIVE, $item1->status);

        $this->assertSame($course2->id, $item2->referenceid);
        $this->assertSame($course2->fullname, $item2->name);
        $this->assertSame('course', $item2->type);
        $this->assertSame((string)util::STATUS_ACTIVE, $item2->status);
    }

    public function test_update(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section();

        $item0 = course::create((object)[
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);

        $now = time();

        $this->setCurrentTimeStart();
        $item = course::update((object)[
            'id' => $item0->id,
            'syncname' => '0',
            'name' => 'Fancy name',
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
        ]);
        $this->assertSame($section1->id, $item->sectionid);
        $this->assertSame('course', $item->type);
        $this->assertSame($course1->id, $item->referenceid);
        $this->assertSame('0', $item->syncname);
        $this->assertSame('Fancy name', $item->name);
        $this->assertSame((string)($now - DAYSECS), $item->hiddenbefore);
        $this->assertSame((string)($now + DAYSECS), $item->hiddenafter);
        $this->assertSame((string)util::STATUS_DRAFT, $item->status);
        $this->assertSame($item->timecreated, $item->timecreated);
        $this->assertTimeCurrent($item->timemodified);

        $item = course::update((object)[
            'id' => $item0->id,
            'syncname' => '1',
            'name' => 'Ignored name',
            'sectionid' => $section2->id,
            'type' => 'program',
            'referenceid' => $course2->id,
        ]);
        $this->assertSame($section1->id, $item->sectionid);
        $this->assertSame('course', $item->type);
        $this->assertSame($course1->id, $item->referenceid);
        $this->assertSame('1', $item->syncname);
        $this->assertSame($course1->fullname, $item->name);
    }

    public function test_is_activate_possible(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertTrue(course::is_activate_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertFalse(course::is_activate_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertFalse(course::is_activate_possible($item));
    }

    public function test_activate(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item = course::activate($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item = course::activate($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        try {
            course::activate($item->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only draft items can be activated)', $ex->getMessage());
        }
    }

    public function test_is_archive_possible(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertFalse(course::is_archive_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertTrue(course::is_archive_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertFalse(course::is_archive_possible($item));
    }

    public function test_archive(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item = course::archive($item->id);
        $this->assertSame((string)util::STATUS_ARCHIVED, $item->status);

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $item = course::archive($item->id);
        $this->assertSame((string)util::STATUS_ARCHIVED, $item->status);

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        try {
            course::archive($item->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only active items can be archived)', $ex->getMessage());
        }
    }

    public function test_is_restore_possible(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertFalse(course::is_restore_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertFalse(course::is_restore_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertTrue(course::is_restore_possible($item));
    }

    public function test_restore(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $item = course::restore($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item = course::restore($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        try {
            course::restore($item->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only archived items can be restored)', $ex->getMessage());
        }
    }

    public function test_move(): void {
        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section(['contextid' => $categorycontext->id]);

        $item = course::create((object)[
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);

        $item = course::move($item->id, $section2->id);
        $this->assertSame($section2->id, $item->sectionid);

        $item = course::move($item->id, $section1->id);
        $this->assertSame($section1->id, $item->sectionid);
    }

    public function test_is_delete_possible(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertTrue(course::is_delete_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertTrue(course::is_delete_possible($item));

        $item = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertTrue(course::is_delete_possible($item));
    }

    public function test_delete(): void {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section();
        $collection = $generator->create_collection();

        $item1 = course::create((object)[
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $item2 = course::create((object)[
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $item3 = course::create((object)[
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $ci1 = $generator->create_collection_item(['collectionid' => $collection->id, 'itemid' => $item1->id]);
        $ci2 = $generator->create_collection_item(['collectionid' => $collection->id, 'itemid' => $item2->id]);

        course::delete($item2->id);

        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section1->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_section', ['id' => $section2->id]));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_section', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item1->id]));
        $this->assertFalse($DB->record_exists('tool_mucatalog_item', ['id' => $item2->id]));
        $this->assertTrue($DB->record_exists('tool_mucatalog_item', ['id' => $item3->id]));
        $this->assertSame(2, $DB->count_records('tool_mucatalog_item', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection', ['id' => $collection->id]));
        $this->assertSame(1, $DB->count_records('tool_mucatalog_collection', []));

        $this->assertTrue($DB->record_exists('tool_mucatalog_collection_item', ['collectionid' => $collection->id, 'itemid' => $item1->id]));
        $this->assertSame(1, $DB->count_records('tool_mucatalog_collection_item', []));
    }

    public function test_get_description(): void {
        $course1 = $this->getDataGenerator()->create_course(['summary' => 'First description', 'summaryformat' => FORMAT_HTML]);
        $course2 = $this->getDataGenerator()->create_course(['summary' => 'Second description', 'summaryformat' => FORMAT_HTML]);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item2 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $this->assertSame('First description', course::get_description($item1));

        delete_course($course2->id, false);
        $this->assertSame(null, course::get_description($item2));
    }

    public function test_get_reference(): void {
        $syscontext = \context_system::instance();
        $course1 = $this->getDataGenerator()->create_course(['summary' => 'First description', 'summaryformat' => FORMAT_HTML]);
        $coursecontext1 = \context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['summary' => 'Second description', 'summaryformat' => FORMAT_HTML]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teacherroleid = create_role('xteacher', 'xteacher', '');
        assign_capability('moodle/course:view', CAP_ALLOW, $teacherroleid, $syscontext);
        role_assign($teacherroleid, $user1->id, $coursecontext1->id);

        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, 'student');

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item2 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $this->assertSame($course1->fullname, course::get_reference($item1->id));

        $this->setUser($user1);
        $this->assertSame(
            "<a href=\"https://www.example.com/moodle/course/view.php?id=$course1->id\">$course1->fullname</a>",
            course::get_reference($item1->id)
        );
        $this->assertSame($course2->fullname, course::get_reference($item2->id));

        $this->setUser($user2);
        $this->assertSame($course1->fullname, course::get_reference($item1->id));
        $this->assertSame(
            "<a href=\"https://www.example.com/moodle/course/view.php?id=$course2->id\">$course2->fullname</a>",
            course::get_reference($item2->id)
        );

        $this->setAdminUser();
        delete_course($course2->id, false);
        $this->assertSame('Error', course::get_reference($item2->id));
    }

    public function test_get_image_url(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $fs = get_file_storage();
        $draftid = file_get_unused_draft_itemid();
        $filerecord = [
            'contextid' => \context_user::instance($user->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'image.jpeg',
        ];
        $fs->create_file_from_pathname($filerecord, __DIR__ . '/../../../fixtures/mm.jpeg');

        $course1 = $this->getDataGenerator()->create_course(['overviewfiles_filemanager' => $draftid]);
        $course2 = $this->getDataGenerator()->create_course([]);

        $this->setUser(null);

        $syscontext = \context_system::instance();
        $coursecontext1 = \context_course::instance($course1->id);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item2 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$coursecontext1->id/course/overviewfiles/image.jpeg",
            course::get_image_url($item1)
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/tool_mucatalog/item_image/$item2->id/geopattern.svg",
            course::get_image_url($item2)
        );
    }

    public function test_is_user_registered(): void {
        $syscontext = \context_system::instance();
        $course1 = $this->getDataGenerator()->create_course(['summary' => 'First description', 'summaryformat' => FORMAT_HTML]);
        $coursecontext1 = \context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['summary' => 'Second description', 'summaryformat' => FORMAT_HTML]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teacherroleid = create_role('xteacher', 'xteacher', '');
        assign_capability('moodle/course:view', CAP_ALLOW, $teacherroleid, $syscontext);
        role_assign($teacherroleid, $user1->id, $coursecontext1->id);

        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, 'student');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item2 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $this->assertFalse(course::is_user_registered($item1, 0));
        $this->assertFalse(course::is_user_registered($item1, guest_user()->id));

        $this->assertFalse(course::is_user_registered($item1, $user1->id));
        $this->assertFalse(course::is_user_registered($item2, $user1->id));

        $this->assertFalse(course::is_user_registered($item1, $user2->id));
        $this->assertTrue(course::is_user_registered($item2, $user2->id));
    }

    public function test_get_open_url(): void {
        $syscontext = \context_system::instance();
        $course1 = $this->getDataGenerator()->create_course(['summary' => 'First description', 'summaryformat' => FORMAT_HTML]);
        $coursecontext1 = \context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['summary' => 'Second description', 'summaryformat' => FORMAT_HTML]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teacherroleid = create_role('xteacher', 'xteacher', '');
        assign_capability('moodle/course:view', CAP_ALLOW, $teacherroleid, $syscontext);
        role_assign($teacherroleid, $user1->id, $coursecontext1->id);

        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, 'student');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item2 = course::create((object)[
            'sectionid' => $section->id,
            'type' => 'course',
            'referenceid' => $course2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $this->assertSame(
            "https://www.example.com/moodle/course/view.php?id=$course1->id",
            course::get_open_url($item1)->out(false)
        );

        delete_course($course2->id, false);
        $this->assertSame(null, course::get_open_url($item2));
    }
}
