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

use tool_mucatalog\external\form_autocomplete\items_create_certificationids;
use tool_mucatalog\local\util;
use core\exception\moodle_exception;

/**
 * Autocomplete WS for adding certifications to sections tests.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\external\form_autocomplete\items_create_certificationids
 */
final class items_create_certificationids_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        if (!\tool_mulib\local\mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify is not available');
        }
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $certification0 = $certificationgenerator->create_certification([
            'contextid' => $syscontext->id,
            'fullname' => 'Certification 1',
            'idnumber' => 'CT1',
        ]);
        $certification1 = $certificationgenerator->create_certification([
            'contextid' => $catcontext1->id,
            'fullname' => 'Certification 2',
            'idnumber' => 'CT2',
        ]);
        $certification2 = $certificationgenerator->create_certification([
            'contextid' => $catcontext2->id,
            'fullname' => 'Certification 3',
            'idnumber' => 'CT3',
        ]);

        $section0 = $generator->create_section(['contextid' => $syscontext->id]);
        $section1 = $generator->create_section(['contextid' => $catcontext1->id, 'status' => util::STATUS_ARCHIVED]);
        $section2 = $generator->create_section(['contextid' => $catcontext2->id, 'status' => util::STATUS_DRAFT]);

        $item1 = $generator->create_item([
            'sectionid' => $section1->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
        ]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/mucatalog:addcertification', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $catcontext2->id);

        $this->setUser($user1);

        $result = items_create_certificationids::execute('', $section0->id);
        $result = items_create_certificationids::clean_returnvalue(items_create_certificationids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(3, $result['list']);
        $certification = $result['list'][0];
        $this->assertSame((int)$certification0->id, $certification['value']);
        $this->assertSame($certification0->fullname, $certification['label']);
        $certification = $result['list'][1];
        $this->assertSame((int)$certification1->id, $certification['value']);
        $this->assertSame($certification1->fullname, $certification['label']);
        $certification = $result['list'][2];
        $this->assertSame((int)$certification2->id, $certification['value']);
        $this->assertSame($certification2->fullname, $certification['label']);

        $this->assertNull(items_create_certificationids::validate_value($certification0->id, ['sectionid' => $section0->id], $syscontext));
        $this->assertNull(items_create_certificationids::validate_value($certification1->id, ['sectionid' => $section0->id], $syscontext));
        $this->assertNull(items_create_certificationids::validate_value($certification2->id, ['sectionid' => $section0->id], $syscontext));

        $result = items_create_certificationids::execute('', $section1->id);
        $result = items_create_certificationids::clean_returnvalue(items_create_certificationids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(2, $result['list']);
        $certification = $result['list'][0];
        $this->assertSame((int)$certification0->id, $certification['value']);
        $this->assertSame($certification0->fullname, $certification['label']);
        $certification = $result['list'][1];
        $this->assertSame((int)$certification2->id, $certification['value']);
        $this->assertSame($certification2->fullname, $certification['label']);

        $this->assertNull(items_create_certificationids::validate_value($certification0->id, ['sectionid' => $section1->id], $catcontext1));
        $this->assertSame('Error', items_create_certificationids::validate_value($certification1->id, ['sectionid' => $section1->id], $catcontext1));
        $this->assertNull(items_create_certificationids::validate_value($certification2->id, ['sectionid' => $section1->id], $catcontext1));

        $result = items_create_certificationids::execute('ion 1', $section0->id);
        $result = items_create_certificationids::clean_returnvalue(items_create_certificationids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $certification = $result['list'][0];
        $this->assertSame((int)$certification0->id, $certification['value']);
        $this->assertSame($certification0->fullname, $certification['label']);

        $result = items_create_certificationids::execute('T1', $section0->id);
        $result = items_create_certificationids::clean_returnvalue(items_create_certificationids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $certification = $result['list'][0];
        $this->assertSame((int)$certification0->id, $certification['value']);
        $this->assertSame($certification0->fullname, $certification['label']);

        $this->setUser($user2);

        $result = items_create_certificationids::execute('', $section2->id);
        $result = items_create_certificationids::clean_returnvalue(items_create_certificationids::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $certification = $result['list'][0];
        $this->assertSame((int)$certification2->id, $certification['value']);
        $this->assertSame($certification2->fullname, $certification['label']);

        $this->assertSame('Error', items_create_certificationids::validate_value($certification0->id, ['sectionid' => $section2->id], $catcontext2));
        $this->assertSame('Error', items_create_certificationids::validate_value($certification1->id, ['sectionid' => $section2->id], $catcontext2));
        $this->assertNull(items_create_certificationids::validate_value($certification2->id, ['sectionid' => $section2->id], $catcontext2));

        try {
            items_create_certificationids::execute('', $section1->id);
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
