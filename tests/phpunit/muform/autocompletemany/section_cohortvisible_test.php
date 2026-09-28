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

use tool_mucatalog\muform\autocompletemany\section_cohortvisible;
use tool_mulib\local\mulib;

/**
 * Section visibility cohorts autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\muform\autocompletemany\section_cohortvisible
 */
final class section_cohortvisible_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search(): void {
        global $DB;

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $section1 = $generator->create_section();
        $section2 = $generator->create_section(['contextid' => $catcontext1->id]);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]
        );
        $cohort3 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]
        );

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

        $all = [
            (int)$cohort1->id => $cohort1->name,
            (int)$cohort2->id => $cohort2->name,
            (int)$cohort3->id => $cohort3->name,
        ];

        // New section.
        $source = new section_cohortvisible(0, (int)$syscontext->id);
        $this->assertSame([0, (int)$syscontext->id], $source->get_args());
        $this->assertSame($all, $source->search('', 50, []));
        $source = new section_cohortvisible(0, (int)$catcontext1->id);
        $this->assertSame($all, $source->search('', 50, []));

        // Existing section.
        $source = new section_cohortvisible((int)$section1->id, 0);
        $this->assertSame([(int)$section1->id, 0], $source->get_args());
        $this->assertSame($all, $source->search('', 50, []));
        $this->assertSame([(int)$cohort1->id => $cohort1->name], $source->search('ta 1', 50, []));
        $this->assertSame(
            [(int)$cohort1->id => $cohort1->name, (int)$cohort3->id => $cohort3->name],
            $source->search('', 50, [(string)$cohort2->id])
        );
        $this->assertNull($source->search('', 2, []));

        $this->setUser($user2);

        $source = new section_cohortvisible((int)$section2->id, 0);
        $expected = [
            (int)$cohort2->id => $cohort2->name,
            (int)$cohort3->id => $cohort3->name,
        ];
        $this->assertSame($expected, $source->search('', 50, []));

        try {
            new section_cohortvisible((int)$section1->id, 0);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
        try {
            new section_cohortvisible(0, (int)$syscontext->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }

        $this->setUser($user3);

        $source = new section_cohortvisible((int)$section2->id, 0);
        $this->assertSame([(int)$cohort3->id => $cohort3->name], $source->search('', 50, []));
        $source = new section_cohortvisible(0, (int)$catcontext1->id);
        $this->assertSame([(int)$cohort3->id => $cohort3->name], $source->search('', 50, []));
    }

    public function test_search_tenant(): void {
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

        $section0 = $generator->create_section([]);
        $section1 = $generator->create_section(['contextid' => $tenant1catcontext->id]);

        $cohort0 = $this->getDataGenerator()->create_cohort(['visible' => 1]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant1catcontext->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant2catcontext->id]);

        $user1 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);

        $this->setUser($user1);

        // NOTE: tenant cohorts are created in system context - they should be visible here.

        $expected = [
            (int)$cohort0->id => $cohort0->name,
            (int)$cohort1->id => $cohort1->name,
            (int)$cohort2->id => $cohort2->name,
            (int)$tenantcohort1->id => $tenantcohort1->name,
            (int)$tenantcohort2->id => $tenantcohort2->name,
        ];
        $source = new section_cohortvisible(0, (int)$syscontext->id);
        $this->assertSame($expected, $source->search('', 50, []));
        $source = new section_cohortvisible((int)$section0->id, 0);
        $this->assertSame($expected, $source->search('', 50, []));
        $this->assertSame([], $source->validate([(string)$cohort2->id]));

        $expected = [
            (int)$cohort0->id => $cohort0->name,
            (int)$cohort1->id => $cohort1->name,
            (int)$tenantcohort1->id => $tenantcohort1->name,
            (int)$tenantcohort2->id => $tenantcohort2->name,
        ];
        $source = new section_cohortvisible(0, (int)$tenant1catcontext->id);
        $this->assertSame($expected, $source->search('', 50, []));
        $source = new section_cohortvisible((int)$section1->id, 0);
        $this->assertSame($expected, $source->search('', 50, []));

        // Cohorts of other tenants are not allowed.
        $this->assertSame([], $source->labels([(string)$cohort2->id]));
        $this->assertSame([(string)$cohort2->id => 'Error'], $source->validate([(string)$cohort2->id]));
        $this->assertSame([(int)$cohort1->id => $cohort1->name], $source->labels([(string)$cohort1->id]));
        $this->assertSame([], $source->validate([(string)$cohort1->id]));
    }

    public function test_labels_validate(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]
        );
        $cohort3 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]
        );

        $section1 = $generator->create_section([
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);
        $section2 = $generator->create_section([
            'contextid' => $catcontext1->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext);
        role_assign($editorroleid, $user2->id, $syscontext);
        role_assign($editorroleid, $user3->id, $syscontext);

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $syscontext);
        role_assign($viewerroleid, $user2->id, $catcontext1);

        $all = [(string)$cohort1->id, (string)$cohort2->id, (string)$cohort3->id];
        $alllabels = [
            (int)$cohort1->id => $cohort1->name,
            (int)$cohort2->id => $cohort2->name,
            (int)$cohort3->id => $cohort3->name,
        ];

        $this->setUser($user1);

        $sources = [
            new section_cohortvisible(0, (int)$syscontext->id),
            new section_cohortvisible(0, (int)$catcontext1->id),
            new section_cohortvisible((int)$section1->id, 0),
            new section_cohortvisible((int)$section2->id, 0),
        ];
        foreach ($sources as $source) {
            $this->assertSame($alllabels, $source->labels($all));
            $this->assertSame([], $source->validate($all));
        }

        // Unknown and invalid values.
        $source = new section_cohortvisible((int)$section2->id, 0);
        $this->assertSame([], $source->labels(['0', '-1', 'abc', '', (string)($cohort3->id + 100)]));
        $this->assertSame(
            [(string)($cohort3->id + 100) => 'Error'],
            $source->validate([(string)($cohort3->id + 100)])
        );

        $this->setUser($user2);

        $expected = [(int)$cohort2->id => $cohort2->name, (int)$cohort3->id => $cohort3->name];
        $sources = [
            new section_cohortvisible(0, (int)$syscontext->id),
            new section_cohortvisible(0, (int)$catcontext1->id),
        ];
        foreach ($sources as $source) {
            $this->assertSame($expected, $source->labels($all));
            $this->assertSame([(string)$cohort1->id => 'Error'], $source->validate($all));
        }
        $source = new section_cohortvisible((int)$section2->id, 0);
        $this->assertSame($expected, $source->labels($all));
        $this->assertSame([(string)$cohort1->id => 'Error'], $source->validate($all));

        // Cohorts already used by the section are always allowed.
        $source = new section_cohortvisible((int)$section1->id, 0);
        $this->assertSame($alllabels, $source->labels($all));
        $this->assertSame([], $source->validate($all));

        $this->setUser($user3);

        $expected = [(int)$cohort3->id => $cohort3->name];
        $errors = [(string)$cohort1->id => 'Error', (string)$cohort2->id => 'Error'];
        $sources = [
            new section_cohortvisible(0, (int)$syscontext->id),
            new section_cohortvisible(0, (int)$catcontext1->id),
        ];
        foreach ($sources as $source) {
            $this->assertSame($expected, $source->labels($all));
            $this->assertSame($errors, $source->validate($all));
        }
        $source = new section_cohortvisible((int)$section2->id, 0);
        $this->assertSame($expected, $source->labels($all));
        $this->assertSame($errors, $source->validate($all));

        $source = new section_cohortvisible((int)$section1->id, 0);
        $this->assertSame(
            [(int)$cohort1->id => $cohort1->name, (int)$cohort3->id => $cohort3->name],
            $source->labels($all)
        );
        $this->assertSame([(string)$cohort2->id => 'Error'], $source->validate($all));
    }
}
