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

namespace tool_mucatalog\phpunit\external\form_autocomplete;

use tool_mucatalog\external\form_autocomplete\collection_cohortvisible;
use tool_mulib\local\mulib;

/**
 * External API for collection visibility cohorts test.
 *
 * @group      MuTMS
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\external\form_autocomplete\collection_cohortvisible
 */
final class collection_cohortvisible_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execution(): void {
        global $DB;

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $collection1 = $generator->create_collection();
        $collection2 = $generator->create_collection(['contextid' => $catcontext1->id]);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);
        role_assign($managerrole->id, $user2->id, $catcontext1);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $this->setUser($user1);

        $result = collection_cohortvisible::execute('', null, $syscontext->id);
        $result = collection_cohortvisible::clean_returnvalue(collection_cohortvisible::execute_returns(), $result);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => (int)$cohort1->id, 'label' => $cohort1->name],
            ['value' => (int)$cohort2->id, 'label' => $cohort2->name],
            ['value' => (int)$cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = collection_cohortvisible::execute('', null, $catcontext1->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = collection_cohortvisible::execute('', $collection1->id, null);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = collection_cohortvisible::execute('', $collection1->id, null);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = collection_cohortvisible::execute('ta 1', $collection1->id, null);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($user2);

        $result = collection_cohortvisible::execute('', $collection2->id, null);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($user3);

        $result = collection_cohortvisible::execute('', $collection2->id, null);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);
    }

    public function test_execution_tenant(): void {
        global $DB;

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
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $tenantcohort1 = $DB->get_record('cohort', ['id' => $tenant1->cohortid]);
        $tenantcohort2 = $DB->get_record('cohort', ['id' => $tenant2->cohortid]);

        $collection0 = $generator->create_collection([]);
        $collection1 = $generator->create_collection(['contextid' => $tenant1catcontext->id]);
        $collection2 = $generator->create_collection(['contextid' => $tenant2catcontext->id]);

        $cohort0 = $this->getDataGenerator()->create_cohort(['visible' => 1]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant1catcontext->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant2catcontext->id]);

        $user1 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);

        $this->setUser($user1);

        // NOTE: tenant cohorts are created in system context - they should be visible here.

        $result = collection_cohortvisible::execute('', null, $syscontext->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort0->id, 'label' => $cohort0->name],
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $tenantcohort1->id, 'label' => $tenantcohort1->name],
            ['value' => $tenantcohort2->id, 'label' => $tenantcohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = collection_cohortvisible::execute('', $collection0->id, null);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort0->id, 'label' => $cohort0->name],
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $tenantcohort1->id, 'label' => $tenantcohort1->name],
            ['value' => $tenantcohort2->id, 'label' => $tenantcohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = collection_cohortvisible::execute('', null, $tenant1catcontext->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort0->id, 'label' => $cohort0->name],
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $tenantcohort1->id, 'label' => $tenantcohort1->name],
            ['value' => $tenantcohort2->id, 'label' => $tenantcohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = collection_cohortvisible::execute('', $collection1->id, null);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort0->id, 'label' => $cohort0->name],
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $tenantcohort1->id, 'label' => $tenantcohort1->name],
            ['value' => $tenantcohort2->id, 'label' => $tenantcohort2->name],
        ];
        $this->assertSame($expected, $result['list']);
    }

    public function test_validate_value(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]);

        $collection1 = $generator->create_collection([
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);
        $collection2 = $generator->create_collection([
            'contextid' => $catcontext1->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $syscontext);
        role_assign($viewerroleid, $user2->id, $catcontext1);

        $this->setUser($user1);

        $this->assertNull(collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => null, 'contextid' => $syscontext->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => null, 'contextid' => $syscontext->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => null, 'contextid' => $syscontext->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => null, 'contextid' => $catcontext1->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => null, 'contextid' => $catcontext1->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => null, 'contextid' => $catcontext1->id], $catcontext1));

        $this->assertNull(collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => $collection1->id, 'contextid' => null], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => $collection1->id, 'contextid' => null], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => $collection1->id, 'contextid' => null], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => $collection2->id, 'contextid' => null], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => $collection2->id, 'contextid' => null], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => $collection2->id, 'contextid' => null], $catcontext1));

        $this->setUser($user2);

        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort1->id, ['contextid' => $syscontext->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['contextid' => $syscontext->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['contextid' => $syscontext->id], $syscontext));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort1->id, ['contextid' => $catcontext1->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['contextid' => $catcontext1->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['contextid' => $catcontext1->id], $catcontext1));

        $this->assertNull(collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => $collection1->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => $collection1->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => $collection1->id], $syscontext));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => $collection2->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => $collection2->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => $collection2->id], $catcontext1));

        $this->setUser($user3);

        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort1->id, ['contextid' => $syscontext->id], $syscontext));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort2->id, ['contextid' => $syscontext->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['contextid' => $syscontext->id], $syscontext));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort1->id, ['contextid' => $catcontext1->id], $catcontext1));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort2->id, ['contextid' => $catcontext1->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['contextid' => $catcontext1->id], $catcontext1));

        $this->assertNull(collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => $collection1->id], $syscontext));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => $collection1->id], $syscontext));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => $collection1->id], $syscontext));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort1->id, ['collectionid' => $collection2->id], $catcontext1));
        $this->assertSame('Error', collection_cohortvisible::validate_value($cohort2->id, ['collectionid' => $collection2->id], $catcontext1));
        $this->assertNull(collection_cohortvisible::validate_value($cohort3->id, ['collectionid' => $collection2->id], $catcontext1));
    }
}
