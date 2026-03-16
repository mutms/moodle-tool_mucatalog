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

use tool_mucatalog\external\form_autocomplete\item_move_sectionid;
use tool_mucatalog\local\util;
use core\exception\moodle_exception;

/**
 * Autocomplete WS for moving items to other sections tests.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\external\form_autocomplete\item_move_sectionid
 */
final class item_move_sectionid_test extends \advanced_testcase {
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

        $course = $this->getDataGenerator()->create_course();

        $section0 = $generator->create_section([
            'contextid' => $syscontext->id,
            'name' => 'Fancy section',
            'shortdescription' => 'Just a small description',
        ]);
        $section1 = $generator->create_section(['contextid' => $catcontext1->id, 'status' => util::STATUS_ARCHIVED]);
        $section2 = $generator->create_section(['contextid' => $catcontext2->id]);
        $section3 = $generator->create_section(['contextid' => $catcontext2->id, 'status' => util::STATUS_DRAFT]);

        $item0 = $generator->create_item([
            'sectionid' => $section0->id,
            'type' => 'course',
            'referenceid' => $course->id,
        ]);
        $item1 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course->id,
        ]);
        $item2 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course->id,
        ]);
        $item3 = $generator->create_item([
            'sectionid' => $section3->id,
            'type' => 'course',
            'referenceid' => $course->id,
        ]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $catcontext2->id);

        $this->setUser($user1);

        $result = item_move_sectionid::execute('', $item0->id);
        $result = item_move_sectionid::clean_returnvalue(item_move_sectionid::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(3, $result['list']);
        $section = $result['list'][0];
        $this->assertSame((int)$section1->id, $section['value']);
        $this->assertSame($section1->name, $section['label']);
        $section = $result['list'][1];
        $this->assertSame((int)$section2->id, $section['value']);
        $this->assertSame($section2->name, $section['label']);
        $section = $result['list'][2];
        $this->assertSame((int)$section3->id, $section['value']);
        $this->assertSame($section3->name, $section['label']);

        $this->assertSame('Error', item_move_sectionid::validate_value($section0->id, ['itemid' => $item0->id], $syscontext));
        $this->assertNull(item_move_sectionid::validate_value($section1->id, ['itemid' => $item0->id], $syscontext));
        $this->assertNull(item_move_sectionid::validate_value($section2->id, ['itemid' => $item0->id], $syscontext));
        $this->assertNull(item_move_sectionid::validate_value($section3->id, ['itemid' => $item0->id], $syscontext));

        $result = item_move_sectionid::execute('fancy', $item1->id);
        $result = item_move_sectionid::clean_returnvalue(item_move_sectionid::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $section = $result['list'][0];
        $this->assertSame((int)$section0->id, $section['value']);
        $this->assertSame($section0->name, $section['label']);

        $result = item_move_sectionid::execute('small', $item1->id);
        $result = item_move_sectionid::clean_returnvalue(item_move_sectionid::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $section = $result['list'][0];
        $this->assertSame((int)$section0->id, $section['value']);
        $this->assertSame($section0->name, $section['label']);

        $this->setUser($user2);

        $result = item_move_sectionid::execute('', $item2->id);
        $result = item_move_sectionid::clean_returnvalue(item_move_sectionid::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $section = $result['list'][0];
        $this->assertSame((int)$section3->id, $section['value']);
        $this->assertSame($section3->name, $section['label']);

        $this->assertSame('Error', item_move_sectionid::validate_value($section0->id, ['itemid' => $item2->id], $catcontext2));
        $this->assertSame('Error', item_move_sectionid::validate_value($section1->id, ['itemid' => $item2->id], $catcontext2));
        $this->assertSame('Error', item_move_sectionid::validate_value($section2->id, ['itemid' => $item2->id], $catcontext2));
        $this->assertNull(item_move_sectionid::validate_value($section3->id, ['itemid' => $item2->id], $catcontext2));

        try {
            item_move_sectionid::execute('', $item1->id);
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
