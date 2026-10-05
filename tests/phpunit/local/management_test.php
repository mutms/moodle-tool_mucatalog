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

use tool_mucatalog\local\management;
use tool_mucatalog\local\util;

/**
 * Catalogue management helper test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\management
 */
final class management_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_reference_sections(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $syscontext = \context_system::instance();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $this->assertSame([], management::get_reference_sections('course', $course1->id));

        $section1 = $generator->create_section([
            'name' => 'Section C',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $section2 = $generator->create_section([
            'name' => 'Section A',
            'contextid' => $catcontext1->id,
            'status' => util::STATUS_DRAFT,
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);
        $section3 = $generator->create_section([
            'name' => 'Section B',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 0,
            'uservisible' => 1,
        ]);
        $section3 = \tool_mucatalog\local\section::archive($section3->id);

        $now = time();
        $item1 = $generator->create_item(['sectionid' => $section1->id, 'type' => 'course', 'referenceid' => $course1->id]);
        $item2 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item3 = $generator->create_item(['sectionid' => $section3->id, 'type' => 'course', 'referenceid' => $course1->id]);
        $item3 = \tool_mucatalog\local\item\course::update((object)[
            'id' => $item3->id,
            'hiddenbefore' => $now - 100,
            'hiddenafter' => $now + 100,
        ]);
        $item4 = $generator->create_item(['sectionid' => $section1->id, 'type' => 'course', 'referenceid' => $course2->id]);

        $this->assertSame([], management::get_reference_sections('course', $course3->id));
        $this->assertSame([], management::get_reference_sections('program', $course1->id));

        $records = management::get_reference_sections('course', $course2->id);
        $this->assertSame([(int)$item4->id], array_keys($records));

        // Ordered by section name.
        $records = management::get_reference_sections('course', $course1->id);
        $this->assertSame([(int)$item2->id, (int)$item3->id, (int)$item1->id], array_keys($records));

        $record = $records[$item1->id];
        $this->assertSame($item1->id, $record->id);
        $this->assertSame($item1->name, $record->name);
        $this->assertEquals(util::STATUS_ACTIVE, $record->status);
        $this->assertNull($record->hiddenbefore);
        $this->assertNull($record->hiddenafter);
        $this->assertSame($section1->id, $record->sectionid);
        $this->assertSame('Section C', $record->sectionname);
        $this->assertEquals($syscontext->id, $record->sectioncontextid);
        $this->assertEquals(util::STATUS_ACTIVE, $record->sectionstatus);
        $this->assertEquals(1, $record->sectionguestvisible);
        $this->assertEquals(1, $record->sectionuservisible);

        $record = $records[$item2->id];
        $this->assertSame($item2->id, $record->id);
        $this->assertEquals(util::STATUS_DRAFT, $record->status);
        $this->assertSame($section2->id, $record->sectionid);
        $this->assertSame('Section A', $record->sectionname);
        $this->assertEquals($catcontext1->id, $record->sectioncontextid);
        $this->assertEquals(util::STATUS_DRAFT, $record->sectionstatus);
        $this->assertEquals(0, $record->sectionguestvisible);
        $this->assertEquals(0, $record->sectionuservisible);

        $record = $records[$item3->id];
        $this->assertSame($item3->id, $record->id);
        $this->assertEquals(util::STATUS_ACTIVE, $record->status);
        $this->assertEquals($now - 100, $record->hiddenbefore);
        $this->assertEquals($now + 100, $record->hiddenafter);
        $this->assertSame($section3->id, $record->sectionid);
        $this->assertSame('Section B', $record->sectionname);
        $this->assertEquals($syscontext->id, $record->sectioncontextid);
        $this->assertEquals(util::STATUS_ARCHIVED, $record->sectionstatus);
        $this->assertEquals(0, $record->sectionguestvisible);
        $this->assertEquals(1, $record->sectionuservisible);
    }

    public function test_get_reference_add_button(): void {
        global $PAGE;

        $syscontext = \context_system::instance();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $returnurl = new \core\url('/course/view.php', ['id' => $course->id]);

        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:addcourse', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user1->id, $coursecontext->id);

        $this->setUser($user1);
        $button = management::get_reference_add_button('course', $course->id, $returnurl);
        $this->assertInstanceOf(\tool_mulib\output\muform\dialog\button::class, $button);
        $PAGE->set_url('/');
        $PAGE->set_context($syscontext);
        $html = $PAGE->get_renderer('core')->render($button);
        $this->assertStringContainsString('Add to catalogue section', $html);
        $this->assertStringContainsString('/admin/tool/mucatalog/management/reference_add.php?type=course&amp;referenceid=' . $course->id, $html);
        $this->assertStringContainsString('returnurl=' . rawurlencode('/course/view.php?id=' . $course->id), $html);

        $this->assertNull(management::get_reference_add_button('course', $course->id + 100, $returnurl));
        $this->assertNull(management::get_reference_add_button('xyz', $course->id, $returnurl));

        $this->setUser($user2);
        $this->assertNull(management::get_reference_add_button('course', $course->id, $returnurl));
    }

    public function test_get_sections_management_url(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $coursecontext = \context_course::instance($course->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $managerroleid, $syscontext);
        assign_capability('tool/mucatalog:view', CAP_ALLOW, $managerroleid, $syscontext);
        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($managerroleid, $user1->id, $catcontext->id);
        role_assign($viewerroleid, $user2->id, $syscontext->id);
        role_assign($editorroleid, $user3->id, $syscontext->id);

        $this->setUser($user1);
        $url = management::get_sections_management_url($catcontext);
        $this->assertSame('/admin/tool/mucatalog/management/sections.php?contextid=' . $catcontext->id, $url->out_as_local_url(false));
        $this->assertNull(management::get_sections_management_url($syscontext));
        $this->assertNull(management::get_sections_management_url($coursecontext));

        $this->setUser($user2);
        $this->assertNull(management::get_sections_management_url($catcontext));
        $this->assertNull(management::get_sections_management_url($syscontext));

        $this->setUser($user3);
        $this->assertNull(management::get_sections_management_url($catcontext));

        $this->setAdminUser();
        $url = management::get_sections_management_url($syscontext);
        $this->assertSame('/admin/tool/mucatalog/management/sections.php?contextid=' . $syscontext->id, $url->out_as_local_url(false));
    }
}
