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

namespace tool_mucatalog\phpunit\output\management;

use tool_mucatalog\local\util;

/**
 * Catalogue management renderer test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\output\management\renderer
 */
final class renderer_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_render_reference_sections(): void {
        global $PAGE;

        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();

        $section1 = $generator->create_section([
            'name' => 'Section A',
            'status' => util::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $section2 = $generator->create_section([
            'name' => 'Section B',
            'status' => util::STATUS_DRAFT,
            'guestvisible' => 0,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
        ]);
        $item1 = $generator->create_item(['sectionid' => $section1->id, 'type' => 'course', 'referenceid' => $course1->id]);
        $hiddenbefore = make_timestamp(2025, 12, 24, 10, 30);
        $hiddenafter = make_timestamp(2035, 1, 2, 8, 5);
        $item2 = $generator->create_item([
            'sectionid' => $section2->id,
            'type' => 'course',
            'referenceid' => $course1->id,
            'hiddenbefore' => $hiddenbefore,
            'hiddenafter' => $hiddenafter,
            'status' => util::STATUS_DRAFT,
        ]);

        $PAGE->set_url('/');
        $PAGE->set_context($syscontext);
        /** @var \tool_mucatalog\output\management\renderer $renderer */
        $renderer = $PAGE->get_renderer('tool_mucatalog', 'management');
        $dateformat = get_string('strftimedatetimeshort', 'langconfig');

        $cells = function (string $html): array {
            $this->assertStringContainsString('id="tool_mucatalog_reference_sections"', $html);
            $rows = [];
            preg_match_all('|<tr[^>]*>(.*?)</tr>|s', $html, $trs);
            foreach ($trs[1] as $tr) {
                preg_match_all('|<t[hd][^>]*>(.*?)</t[hd]>|s', $tr, $tds);
                $rows[] = array_map(fn($td) => trim(strip_tags($td)), $tds[1]);
            }
            return $rows;
        };

        $this->setAdminUser();
        $html = $renderer->render_reference_sections('course', $course1->id);
        $expected = [
            ['Section', 'Management category', 'Section status', 'Visible to', 'Item status', 'Hidden before', 'Hidden after'],
            ['Section A', 'System', 'Active', 'Guests, All users', 'Active', '-', '-'],
            ['Section B', 'System', 'Draft', 'Cohort 1', 'Draft', userdate($hiddenbefore, $dateformat), userdate($hiddenafter, $dateformat)],
        ];
        $this->assertSame($expected, $cells($html));
        $this->assertStringContainsString('/admin/tool/mucatalog/management/section.php?id=' . $section2->id, $html);
        $this->assertStringContainsString('/admin/tool/mucatalog/management/item.php?id=' . $item2->id, $html);

        // No links without catalogue view capability.
        $this->setUser($user1);
        $html = $renderer->render_reference_sections('course', $course1->id);
        $this->assertSame($expected, $cells($html));
        $this->assertStringNotContainsString('/admin/tool/mucatalog/management/', $html);

        $html = $renderer->render_reference_sections('course', $course2->id);
        $this->assertStringContainsString('Not included in any catalogue section', $html);
        $this->assertStringNotContainsString('tool_mucatalog_reference_sections', $html);
    }
}
