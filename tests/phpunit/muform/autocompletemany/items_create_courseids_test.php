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

namespace tool_mucatalog\phpunit\muform\autocompletemany;

use tool_mucatalog\muform\autocompletemany\items_create_courseids;
use tool_mucatalog\local\util;
use tool_mulib\local\mulib;

/**
 * Section courses autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\muform\autocompletemany\items_create_courseids
 */
final class items_create_courseids_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search_labels(): void {
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

        $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'course',
            'referenceid' => $course1->id,
        ]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/mucatalog:addcourse', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $catcontext2->id);

        $all = [(string)$course0->id, (string)$course1->id, (string)$course2->id];

        $this->setUser($user1);

        $source = new items_create_courseids((int)$section0->id);
        $this->assertSame([(int)$section0->id], $source->get_args());
        $expected = [
            (int)$course0->id => $course0->fullname,
            (int)$course1->id => $course1->fullname,
            (int)$course2->id => $course2->fullname,
        ];
        $this->assertSame($expected, $source->search('', 50, []));
        $this->assertNull($source->search('', 2, []));
        $this->assertSame(
            [(int)$course0->id => $course0->fullname, (int)$course2->id => $course2->fullname],
            $source->search('', 50, [(string)$course1->id])
        );
        $this->assertSame([(int)$course0->id => $course0->fullname], $source->search('z 1', 50, []));
        $this->assertSame([(int)$course0->id => $course0->fullname], $source->search('K1', 50, []));
        $this->assertSame([(int)$course0->id => $course0->fullname], $source->search('Z1', 50, []));
        $this->assertSame($expected, $source->labels($all));
        $this->assertSame([], $source->validate($all));

        // Unknown and invalid values, site course.
        $this->assertSame([], $source->labels(['0', '-1', 'abc', '', (string)SITEID, (string)($course2->id + 100)]));

        // Courses already in the section are excluded.
        $source = new items_create_courseids((int)$section1->id);
        $expected = [
            (int)$course0->id => $course0->fullname,
            (int)$course2->id => $course2->fullname,
        ];
        $this->assertSame($expected, $source->search('', 50, []));
        $this->assertSame($expected, $source->labels($all));
        $this->assertSame([], $source->validate($all));

        $this->setUser($user2);

        $source = new items_create_courseids((int)$section2->id);
        $this->assertSame([(int)$course2->id => $course2->fullname], $source->search('', 50, []));
        $this->assertSame([(int)$course2->id => $course2->fullname], $source->labels($all));
        $this->assertSame([], $source->validate($all));

        try {
            new items_create_courseids((int)$section1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Manage Universal catalogue).',
                $ex->getMessage()
            );
        }
    }

    public function test_search_tenant(): void {
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();

        $course0 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 1']);
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 2', 'category' => $tenant1->categoryid]);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 3', 'category' => $tenant2->categoryid]);
        $all = [(string)$course0->id, (string)$course1->id, (string)$course2->id];

        $section0 = $generator->create_section(['contextid' => $syscontext->id]);
        $section1 = $generator->create_section(['contextid' => $tenant1catcontext->id]);

        $this->setAdminUser();

        $source = new items_create_courseids((int)$section0->id);
        $expected = [
            (int)$course0->id => $course0->fullname,
            (int)$course1->id => $course1->fullname,
            (int)$course2->id => $course2->fullname,
        ];
        $this->assertSame($expected, $source->search('', 50, []));
        $this->assertSame([], $source->validate($all));

        $source = new items_create_courseids((int)$section1->id);
        $expected = [
            (int)$course0->id => $course0->fullname,
            (int)$course1->id => $course1->fullname,
        ];
        $this->assertSame($expected, $source->search('', 50, []));
        $this->assertSame([(string)$course2->id => 'Error'], $source->validate($all));
    }
}
