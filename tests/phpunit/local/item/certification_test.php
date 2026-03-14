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
use tool_mucatalog\local\item\certification;
use tool_mulib\local\mulib;
use core\exception\invalid_parameter_exception;
use core\exception\coding_exception;
use core\exception\moodle_exception;

/**
 * Certification item test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\item\certification
 */
final class certification_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type_classname(): void {
        $this->assertSame(certification::class, item::get_type_classname('certification'));
    }

    public function test_get_type(): void {
        $this->assertSame('certification', certification::get_type());
        $this->assertSame('certification', certification::TYPE);
    }

    public function test_is_available(): void {
        if (mulib::is_mucertify_available()) {
            $this->assertTrue(certification::is_available());
        } else {
            $this->assertFalse(certification::is_available());
        }
    }

    public function test_get_create_form_class(): void {
        $this->assertSame(\tool_mucatalog\local\form\items_create::class, certification::get_create_form_class());
    }

    public function test_get_create_form_referenceids_class(): void {
        $this->assertSame(
            \tool_mucatalog\external\form_autocomplete\items_create_certificationids::class,
            certification::get_create_form_referenceids_class()
        );
    }

    public function test_get_type_name(): void {
        $this->assertSame('Certification', certification::get_type_name());
    }

    public function test_create(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();
        $certification3 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $this->setCurrentTimeStart();
        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
        ]);
        $this->assertSame($section->id, $item->sectionid);
        $this->assertSame('certification', $item->type);
        $this->assertSame($certification1->id, $item->referenceid);
        $this->assertSame('1', $item->syncname);
        $this->assertSame($certification1->fullname, $item->name);
        $this->assertSame(null, $item->hiddenbefore);
        $this->assertSame(null, $item->hiddenafter);
        $this->assertSame((string)util::STATUS_DRAFT, $item->status);
        $this->assertTimeCurrent($item->timecreated);
        $this->assertTimeCurrent($item->timemodified);

        $now = time();

        $this->setCurrentTimeStart();
        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'name' => 'Fancy name',
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertSame($section->id, $item->sectionid);
        $this->assertSame('certification', $item->type);
        $this->assertSame($certification2->id, $item->referenceid);
        $this->assertSame('0', $item->syncname);
        $this->assertSame('Fancy name', $item->name);
        $this->assertSame((string)($now - DAYSECS), $item->hiddenbefore);
        $this->assertSame((string)($now + DAYSECS), $item->hiddenafter);
        $this->assertSame((string)util::STATUS_DRAFT, $item->status);
        $this->assertTimeCurrent($item->timecreated);
        $this->assertTimeCurrent($item->timemodified);

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification3->id,
            'syncname' => '1',
            'name' => 'Ignored name',
        ]);
        $this->assertSame('certification', $item->type);
        $this->assertSame($certification3->id, $item->referenceid);
        $this->assertSame('1', $item->syncname);
        $this->assertSame($certification3->fullname, $item->name);
    }

    public function test_create_multiple(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();
        $certification3 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $items = certification::create_multiple((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceids' => [$certification1->id, $certification2->id],
            'status' => util::STATUS_ACTIVE,
        ]);
        $item1 = array_values($items)[0];
        $item2 = array_values($items)[1];

        $this->assertSame($certification1->id, $item1->referenceid);
        $this->assertSame($certification1->fullname, $item1->name);
        $this->assertSame('certification', $item1->type);
        $this->assertSame((string)util::STATUS_ACTIVE, $item1->status);

        $this->assertSame($certification2->id, $item2->referenceid);
        $this->assertSame($certification2->fullname, $item2->name);
        $this->assertSame('certification', $item2->type);
        $this->assertSame((string)util::STATUS_ACTIVE, $item2->status);
    }

    public function test_update(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();
        $certification3 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section();

        $item0 = certification::create((object)[
            'sectionid' => $section1->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
        ]);

        $now = time();

        $this->setCurrentTimeStart();
        $item = certification::update((object)[
            'id' => $item0->id,
            'syncname' => '0',
            'name' => 'Fancy name',
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
        ]);
        $this->assertSame($section1->id, $item->sectionid);
        $this->assertSame('certification', $item->type);
        $this->assertSame($certification1->id, $item->referenceid);
        $this->assertSame('0', $item->syncname);
        $this->assertSame('Fancy name', $item->name);
        $this->assertSame((string)($now - DAYSECS), $item->hiddenbefore);
        $this->assertSame((string)($now + DAYSECS), $item->hiddenafter);
        $this->assertSame((string)util::STATUS_DRAFT, $item->status);
        $this->assertSame($item->timecreated, $item->timecreated);
        $this->assertTimeCurrent($item->timemodified);

        $item = certification::update((object)[
            'id' => $item0->id,
            'syncname' => '1',
            'name' => 'Ignored name',
            'sectionid' => $section2->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
        ]);
        $this->assertSame($section1->id, $item->sectionid);
        $this->assertSame('certification', $item->type);
        $this->assertSame($certification1->id, $item->referenceid);
        $this->assertSame('1', $item->syncname);
        $this->assertSame($certification1->fullname, $item->name);
    }

    public function test_is_activate_possible(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertTrue(certification::is_activate_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertFalse(certification::is_activate_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertFalse(certification::is_activate_possible($item));
    }

    public function test_activate(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();
        $certification3 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item = certification::activate($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item = certification::activate($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        try {
            certification::activate($item->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only draft items can be activated)', $ex->getMessage());
        }
    }

    public function test_is_archive_possible(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertFalse(certification::is_archive_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertTrue(certification::is_archive_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertFalse(certification::is_archive_possible($item));
    }

    public function test_archive(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();
        $certification3 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item = certification::archive($item->id);
        $this->assertSame((string)util::STATUS_ARCHIVED, $item->status);

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $item = certification::archive($item->id);
        $this->assertSame((string)util::STATUS_ARCHIVED, $item->status);

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        try {
            certification::archive($item->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only active items can be archived)', $ex->getMessage());
        }
    }

    public function test_is_restore_possible(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertFalse(certification::is_restore_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertFalse(certification::is_restore_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertTrue(certification::is_restore_possible($item));
    }

    public function test_restore(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();
        $certification3 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $item = certification::restore($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item = certification::restore($item->id);
        $this->assertSame((string)util::STATUS_ACTIVE, $item->status);

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        try {
            certification::restore($item->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (only archived items can be restored)', $ex->getMessage());
        }
    }

    public function test_move(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $category = $this->getDataGenerator()->create_category([]);
        $categorycontext = \context_coursecat::instance($category->id);

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();
        $certification3 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section(['contextid' => $categorycontext->id]);

        $item = certification::create((object)[
            'sectionid' => $section1->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);

        $item = certification::move($item->id, $section2->id);
        $this->assertSame($section2->id, $item->sectionid);

        $item = certification::move($item->id, $section1->id);
        $this->assertSame($section1->id, $item->sectionid);
    }

    public function test_is_delete_possible(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $this->assertTrue(certification::is_delete_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $this->assertTrue(certification::is_delete_possible($item));

        $item = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ARCHIVED,
        ]);
        $this->assertTrue(certification::is_delete_possible($item));
    }

    public function test_delete(): void {
        global $DB;

        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification();
        $certification2 = $certificationgenerator->create_certification();

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section();
        $collection = $generator->create_collection();

        $item1 = certification::create((object)[
            'sectionid' => $section1->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $item2 = certification::create((object)[
            'sectionid' => $section1->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $item3 = certification::create((object)[
            'sectionid' => $section2->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $ci1 = $generator->create_collection_item(['collectionid' => $collection->id, 'itemid' => $item1->id]);
        $ci2 = $generator->create_collection_item(['collectionid' => $collection->id, 'itemid' => $item2->id]);

        certification::delete($item2->id);

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
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification(['description' => 'First description', 'descriptionformat' => FORMAT_HTML]);
        $certification2 = $certificationgenerator->create_certification(['description' => 'Second description', 'descriptionformat' => FORMAT_HTML]);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item2 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $this->assertSame('First description', certification::get_description($item1));

        \tool_mucertify\local\certification::delete($certification2->id);
        $this->assertSame(null, certification::get_description($item2));
    }

    public function test_get_reference(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $categorycontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $categorycontext2 = \context_coursecat::instance($category2->id);

        $certification1 = $certificationgenerator->create_certification(
            ['summary' => 'First description', 'summaryformat' => FORMAT_HTML, 'contextid' => $categorycontext1->id]
        );
        $certification2 = $certificationgenerator->create_certification(
            ['summary' => 'Second description', 'summaryformat' => FORMAT_HTML, 'contextid' => $categorycontext2->id]
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teacherroleid = create_role('xteacher', 'xteacher', '');
        assign_capability('tool/mucertify:view', CAP_ALLOW, $teacherroleid, $syscontext);
        role_assign($teacherroleid, $user1->id, $categorycontext1->id);

        $certificationgenerator->create_certification_assignment(['certificationid' => $certification2->id, 'userid' => $user2->id]);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item2 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $this->assertSame($certification1->fullname, certification::get_reference($item1->id));

        $this->setUser($user1);
        $this->assertSame(
            "<a href=\"https://www.example.com/moodle/admin/tool/mucertify/management/certification.php?id=$certification1->id\">$certification1->fullname</a>",
            certification::get_reference($item1->id)
        );
        $this->assertSame($certification2->fullname, certification::get_reference($item2->id));

        $this->setUser($user2);
        $this->assertSame($certification1->fullname, certification::get_reference($item1->id));
        $this->assertSame($certification2->fullname, certification::get_reference($item2->id));

        $this->setAdminUser();
        \tool_mucertify\local\certification::delete($certification2->id);
        $this->assertSame('Error', certification::get_reference($item2->id));
    }

    public function test_get_image_url(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $certificationgenerator->create_certification(['image' => '/admin/tool/mucatalog/tests/fixtures/mm.jpeg']);
        $certification2 = $certificationgenerator->create_certification([]);

        $this->setUser(null);

        $syscontext = \context_system::instance();
        $certificationcontext1 = \context::instance_by_id($certification1->contextid);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item2 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/tool_mucatalog/item_image/$item1->id/mm.jpeg",
            certification::get_image_url($item1)
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/tool_mucatalog/item_image/$item2->id/geopattern.svg",
            certification::get_image_url($item2)
        );
    }

    public function test_is_user_registered(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $syscontext = \context_system::instance();

        $certification1 = $certificationgenerator->create_certification(['summary' => 'First description', 'summaryformat' => FORMAT_HTML]);
        $certification2 = $certificationgenerator->create_certification(['summary' => 'Second description', 'summaryformat' => FORMAT_HTML]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teacherroleid = create_role('xteacher', 'xteacher', '');
        assign_capability('tool/mucertify:view', CAP_ALLOW, $teacherroleid, $syscontext);
        role_assign($teacherroleid, $user1->id, $syscontext->id);

        $allocation1 = $certificationgenerator->create_certification_assignment(['certificationid' => $certification2->id, 'userid' => $user1->id]);
        \tool_mucertify\local\source\base::assignment_archive($allocation1->id);
        $allocation2 = $certificationgenerator->create_certification_assignment(['certificationid' => $certification2->id, 'userid' => $user2->id]);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_DRAFT,
        ]);
        $item2 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'status' => util::STATUS_DRAFT,
        ]);

        $this->assertFalse(certification::is_user_registered($item1, 0));
        $this->assertFalse(certification::is_user_registered($item1, guest_user()->id));

        $this->assertFalse(certification::is_user_registered($item1, $user1->id));
        $this->assertFalse(certification::is_user_registered($item2, $user1->id));

        $this->assertFalse(certification::is_user_registered($item1, $user2->id));
        $this->assertTrue(certification::is_user_registered($item2, $user2->id));
    }

    public function test_get_open_url(): void {
        if (!mulib::is_mucertify_available()) {
            $this->markTestSkipped('tool_mucertify not available');
        }

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $categorycontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $categorycontext2 = \context_coursecat::instance($category2->id);

        $certification1 = $certificationgenerator->create_certification(
            ['summary' => 'First description', 'summaryformat' => FORMAT_HTML, 'contextid' => $categorycontext1->id]
        );
        $certification2 = $certificationgenerator->create_certification(
            ['summary' => 'Second description', 'summaryformat' => FORMAT_HTML, 'contextid' => $categorycontext2->id]
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teacherroleid = create_role('xteacher', 'xteacher', '');
        assign_capability('tool/mucertify:view', CAP_ALLOW, $teacherroleid, $syscontext);
        role_assign($teacherroleid, $user1->id, $categorycontext1->id);

        $allocation1 = $certificationgenerator->create_certification_assignment(['certificationid' => $certification2->id, 'userid' => $user1->id]);
        \tool_mucertify\local\source\base::assignment_archive($allocation1->id);
        $allocation2 = $certificationgenerator->create_certification_assignment(['certificationid' => $certification2->id, 'userid' => $user2->id]);

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $this->assertInstanceOf('tool_mucatalog_generator', $generator);

        $section = $generator->create_section();

        $item1 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification1->id,
            'status' => util::STATUS_ACTIVE,
        ]);
        $item2 = certification::create((object)[
            'sectionid' => $section->id,
            'type' => 'certification',
            'referenceid' => $certification2->id,
            'status' => util::STATUS_ACTIVE,
        ]);

        $this->setUser(null);
        $this->assertSame(null, certification::get_open_url($item1));
        $this->assertSame(null, certification::get_open_url($item2));

        $this->setUser($user1);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucertify/management/certification.php?id=$certification1->id",
            certification::get_open_url($item1)->out(false)
        );
        $this->assertSame(null, certification::get_open_url($item2));

        $this->setUser($user2);
        $this->assertSame(null, certification::get_open_url($item1));
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/mucertify/my/certification.php?id=$certification2->id",
            certification::get_open_url($item2)->out(false)
        );

        $this->setAdminUser();
        \tool_mucertify\local\certification::delete($certification2->id);
        $this->assertSame(null, certification::get_open_url($item2));
    }
}
