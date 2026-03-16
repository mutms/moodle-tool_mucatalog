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

use tool_mucatalog\external\form_autocomplete\items_create_courseids;
use tool_mucatalog\local\util;
use core\exception\moodle_exception;

/**
 * Autocomplete WS for adding courses to sections tests.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\external\form_autocomplete\items_create_courseids
 */
final class items_create_courseids_test extends \advanced_testcase {
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

        $course0 = $this->getDataGenerator()->create_course([
            'fullname' => 'Kurz 1',
            'shortname' => 'K1',
            'idnumber' => 'KZ1',
        ]);
        $course1 = $this->getDataGenerator()->create_course([
            'category' => $category1->id,
            'fullname' => 'Kurz 2',
            'shortname' => 'K2',
            'idnumber' => 'KZ2',
        ]);
        $course2 = $this->getDataGenerator()->create_course([
            'category' => $category2->id,
            'fullname' => 'Kurz 3',
            'shortname' => 'K3',
            'idnumber' => 'KZ3',
        ]);

        $section0 = $generator->create_section(['contextid' => $syscontext->id]);
        $section1 = $generator->create_section(['contextid' => $catcontext1->id, 'status' => util::STATUS_ARCHIVED]);
        $section2 = $generator->create_section(['contextid' => $catcontext2->id, 'status' => util::STATUS_DRAFT]);

        $item1 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/mucatalog:addcourse', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $catcontext2->id);

        $this->setUser($user1);

        $result = items_create_courseids::execute('', $section0->id);
        $result = items_create_courseids::clean_returnvalue(items_create_courseids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(3, $result['list']);
        $course = $result['list'][0];
        $this->assertSame((int)$course0->id, $course['value']);
        $this->assertSame($course0->fullname, $course['label']);
        $course = $result['list'][1];
        $this->assertSame((int)$course1->id, $course['value']);
        $this->assertSame($course1->fullname, $course['label']);
        $course = $result['list'][2];
        $this->assertSame((int)$course2->id, $course['value']);
        $this->assertSame($course2->fullname, $course['label']);

        $this->assertNull(items_create_courseids::validate_value($course0->id, ['sectionid' => $section0->id], $syscontext));
        $this->assertNull(items_create_courseids::validate_value($course1->id, ['sectionid' => $section0->id], $syscontext));
        $this->assertNull(items_create_courseids::validate_value($course2->id, ['sectionid' => $section0->id], $syscontext));

        $result = items_create_courseids::execute('', $section1->id);
        $result = items_create_courseids::clean_returnvalue(items_create_courseids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(2, $result['list']);
        $course = $result['list'][0];
        $this->assertSame((int)$course0->id, $course['value']);
        $this->assertSame($course0->fullname, $course['label']);
        $course = $result['list'][1];
        $this->assertSame((int)$course2->id, $course['value']);
        $this->assertSame($course2->fullname, $course['label']);

        $this->assertNull(items_create_courseids::validate_value($course0->id, ['sectionid' => $section1->id], $catcontext1));
        $this->assertSame('Error', items_create_courseids::validate_value($course1->id, ['sectionid' => $section1->id], $catcontext1));
        $this->assertNull(items_create_courseids::validate_value($course2->id, ['sectionid' => $section1->id], $catcontext1));

        $result = items_create_courseids::execute('z 1', $section0->id);
        $result = items_create_courseids::clean_returnvalue(items_create_courseids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $course = $result['list'][0];
        $this->assertSame((int)$course0->id, $course['value']);
        $this->assertSame($course0->fullname, $course['label']);

        $result = items_create_courseids::execute('K1', $section0->id);
        $result = items_create_courseids::clean_returnvalue(items_create_courseids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $course = $result['list'][0];
        $this->assertSame((int)$course0->id, $course['value']);
        $this->assertSame($course0->fullname, $course['label']);

        $result = items_create_courseids::execute('Z1', $section0->id);
        $result = items_create_courseids::clean_returnvalue(items_create_courseids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $course = $result['list'][0];
        $this->assertSame((int)$course0->id, $course['value']);
        $this->assertSame($course0->fullname, $course['label']);

        $this->setUser($user2);

        $result = items_create_courseids::execute('', $section2->id);
        $result = items_create_courseids::clean_returnvalue(items_create_courseids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $course = $result['list'][0];
        $this->assertSame((int)$course2->id, $course['value']);
        $this->assertSame($course2->fullname, $course['label']);

        $this->assertSame('Error', items_create_courseids::validate_value($course0->id, ['sectionid' => $section2->id], $catcontext2));
        $this->assertSame('Error', items_create_courseids::validate_value($course1->id, ['sectionid' => $section2->id], $catcontext2));
        $this->assertNull(items_create_courseids::validate_value($course2->id, ['sectionid' => $section2->id], $catcontext2));

        try {
            items_create_courseids::execute('', $section1->id);
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
