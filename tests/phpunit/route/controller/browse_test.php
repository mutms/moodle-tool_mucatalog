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

namespace tool_mucatalog\phpunit\route\controller;

use core\router\route_loader_interface;
use core\router\util as router_util;
use core\tests\router\route_testcase;
use tool_mucatalog\route\controller\browse;
use tool_mucatalog\local\util;

/**
 * Catalogue browsing page endpoints test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\route\controller\browse
 */
final class browse_test extends route_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Start with a fresh page, each request renders output and tests do more requests in one process.
     */
    private function reset_page(): void {
        global $PAGE, $OUTPUT;
        $PAGE = new \moodle_page();
        $OUTPUT = new \core\output\bootstrap_renderer();
    }

    /**
     * Request rendered items.
     *
     * @param array $params query parameters
     * @return array decoded answer with extra 'itemids' key
     */
    private function get_items(array $params): array {
        $this->reset_page();
        $path = 'tool_mucatalog/browse/items?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $response = $this->process_request('GET', $path, route_loader_interface::ROUTE_GROUP_PAGE);
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        $this->assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        $answer = json_decode((string)$response->getBody(), true);
        $this->assertSame(['html', 'javascript', 'nextlimitfrom', 'hasmore'], array_keys($answer));
        preg_match_all('/data-item-id="(\d+)"/', $answer['html'], $matches);
        $answer['itemids'] = array_map('intval', $matches[1]);
        return $answer;
    }

    /**
     * Request that is expected to be refused.
     *
     * @param array $params query parameters
     * @param string $exceptionclass
     */
    private function assert_refused(array $params, string $exceptionclass): void {
        $this->reset_page();
        $path = 'tool_mucatalog/browse/items?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        try {
            $response = $this->process_request('GET', $path, route_loader_interface::ROUTE_GROUP_PAGE);
        } catch (\Throwable $ex) {
            $this->assertInstanceOf($exceptionclass, $ex);
            return;
        }
        $this->assertGreaterThanOrEqual(300, $response->getStatusCode(), (string)$response->getBody());
    }

    public function test_items_url(): void {
        $this->assertStringEndsWith(
            '/tool_mucatalog/browse/items',
            router_util::get_path_for_callable([browse::class, 'items'])->out(false)
        );
    }

    public function test_items(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        set_config('forcelogin', 0);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort1->id, $user2->id);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $section1 = $generator->create_section(['status' => util::STATUS_ACTIVE, 'guestvisible' => 1, 'uservisible' => 1]);
        $section2 = $generator->create_section(['status' => util::STATUS_ACTIVE, 'guestvisible' => 0, 'uservisible' => 0, 'cohortvisible' => [$cohort1->id]]);
        $section3 = $generator->create_section(['status' => util::STATUS_DRAFT, 'guestvisible' => 1, 'uservisible' => 1]);

        $item1 = $generator->create_item(['sectionid' => $section1->id, 'name' => 'Item 1 alpha', 'type' => 'course', 'referenceid' => $course1->id]);
        $item2 = $generator->create_item(['sectionid' => $section1->id, 'name' => 'Item 2 beta', 'type' => 'course', 'referenceid' => $course2->id]);
        $item3 = $generator->create_item(['sectionid' => $section1->id, 'name' => 'Item 3 draft', 'type' => 'course', 'referenceid' => $course3->id, 'status' => util::STATUS_DRAFT]);
        $item4 = $generator->create_item(['sectionid' => $section2->id, 'name' => 'Item 4 gamma', 'type' => 'course', 'referenceid' => $course1->id]);
        $item5 = $generator->create_item(['sectionid' => $section3->id, 'name' => 'Item 5 delta', 'type' => 'course', 'referenceid' => $course1->id]);

        $collection1 = $generator->create_collection(['guestvisible' => 0, 'uservisible' => 1]);
        $collection2 = $generator->create_collection(['guestvisible' => 0, 'uservisible' => 0, 'cohortvisible' => [$cohort1->id]]);
        $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item1->id]);
        $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item4->id]);
        $generator->create_collection_item(['collectionid' => $collection2->id, 'itemid' => $item2->id]);

        // Normal user.
        $this->setUser($user1);
        $answer = $this->get_items(['sectionid' => 0]);
        $this->assertSame([(int)$item1->id, (int)$item2->id], $answer['itemids']);
        $this->assertSame(12, $answer['nextlimitfrom']);
        $this->assertFalse($answer['hasmore']);
        $this->assertStringContainsString('Item 1 alpha', $answer['html']);
        $this->assertStringContainsString('/admin/tool/mucatalog/item.php?id=' . $item1->id, $answer['html']);
        $this->assertIsString($answer['javascript']);

        $answer = $this->get_items(['sectionid' => $section1->id]);
        $this->assertSame([(int)$item1->id, (int)$item2->id], $answer['itemids']);
        $answer = $this->get_items(['sectionid' => -1 * $collection1->id]);
        $this->assertSame([(int)$item1->id], $answer['itemids']);

        $this->assert_refused(['sectionid' => $section2->id], \core\exception\invalid_parameter_exception::class);
        $this->assert_refused(['sectionid' => $section3->id], \core\exception\invalid_parameter_exception::class);
        $this->assert_refused(['sectionid' => -1 * $collection2->id], \core\exception\invalid_parameter_exception::class);
        $this->assert_refused(['sectionid' => 0, 'limitfrom' => -1], \core\exception\invalid_parameter_exception::class);

        // Paging.
        $answer = $this->get_items(['sectionid' => 0, 'limitnum' => 1]);
        $this->assertSame([(int)$item1->id], $answer['itemids']);
        $this->assertSame(1, $answer['nextlimitfrom']);
        $this->assertTrue($answer['hasmore']);
        $answer = $this->get_items(['sectionid' => 0, 'limitfrom' => 1, 'limitnum' => 1, 'append' => 1]);
        $this->assertSame([(int)$item2->id], $answer['itemids']);
        $this->assertSame(2, $answer['nextlimitfrom']);
        $this->assertFalse($answer['hasmore']);

        // Filters.
        $answer = $this->get_items(['sectionid' => 0, 'search' => 'BETA']);
        $this->assertSame([(int)$item2->id], $answer['itemids']);
        $answer = $this->get_items(['sectionid' => 0, 'type' => 'course']);
        $this->assertSame([(int)$item1->id, (int)$item2->id], $answer['itemids']);
        $answer = $this->get_items(['sectionid' => 0, 'type' => 'program']);
        $this->assertSame([], $answer['itemids']);

        // Information that nothing was found is not included when appending.
        $answer = $this->get_items(['sectionid' => 0, 'search' => 'xyz']);
        $this->assertSame([], $answer['itemids']);
        $this->assertStringContainsString('No items found', $answer['html']);
        $answer = $this->get_items(['sectionid' => 0, 'search' => 'xyz', 'append' => 1]);
        $this->assertSame([], $answer['itemids']);
        $this->assertStringNotContainsString('No items found', $answer['html']);

        // Cohort member.
        $this->setUser($user2);
        $answer = $this->get_items(['sectionid' => 0]);
        $this->assertSame([(int)$item1->id, (int)$item2->id, (int)$item4->id], $answer['itemids']);
        $answer = $this->get_items(['sectionid' => $section2->id]);
        $this->assertSame([(int)$item4->id], $answer['itemids']);
        $answer = $this->get_items(['sectionid' => -1 * $collection1->id]);
        $this->assertSame([(int)$item1->id, (int)$item4->id], $answer['itemids']);
        $answer = $this->get_items(['sectionid' => -1 * $collection2->id]);
        $this->assertSame([(int)$item2->id], $answer['itemids']);

        // Guests and not-logged-in users see sections visible to guests.
        $this->setGuestUser();
        $answer = $this->get_items(['sectionid' => 0]);
        $this->assertSame([(int)$item1->id, (int)$item2->id], $answer['itemids']);
        $this->assert_refused(['sectionid' => -1 * $collection1->id], \core\exception\invalid_parameter_exception::class);

        $this->setUser(null);
        $answer = $this->get_items(['sectionid' => 0]);
        $this->assertSame([(int)$item1->id, (int)$item2->id], $answer['itemids']);

        // Browsing capability is required.
        $syscontext = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucatalog:browse', CAP_PROHIBIT, $roleid, $syscontext);
        role_assign($roleid, $user1->id, $syscontext->id);
        $this->setUser($user1);
        $this->assertFalse(has_capability('tool/mucatalog:browse', \context_system::instance()));
        $this->assert_refused(['sectionid' => 0], \core\exception\required_capability_exception::class);
    }

    public function test_items_forcelogin(): void {
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $course1 = $this->getDataGenerator()->create_course();
        $section1 = $generator->create_section(['status' => util::STATUS_ACTIVE, 'guestvisible' => 1, 'uservisible' => 1]);
        $item1 = $generator->create_item(['sectionid' => $section1->id, 'type' => 'course', 'referenceid' => $course1->id]);

        set_config('forcelogin', 1);

        $this->setUser(null);
        $this->assert_refused(['sectionid' => 0], \core\exception\moodle_exception::class);

        $this->setGuestUser();
        $answer = $this->get_items(['sectionid' => 0]);
        $this->assertSame([(int)$item1->id], $answer['itemids']);
    }

    public function test_items_tenant(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        /** @var \tool_mucatalog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $course0 = $this->getDataGenerator()->create_course(['fullname' => 'Course 0']);
        $course0h = $this->getDataGenerator()->create_course(['fullname' => 'Course 0 hidden']);
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Course 1']);

        $section0 = $generator->create_section(['status' => util::STATUS_ACTIVE, 'uservisible' => 1]);
        $section0h = $generator->create_section(['status' => util::STATUS_ACTIVE, 'uservisible' => 1, 'hiddenfromtenants' => 1]);
        $section1 = $generator->create_section(['status' => util::STATUS_ACTIVE, 'uservisible' => 1, 'contextid' => $catcontext1->id]);
        $item0 = $generator->create_item(['sectionid' => $section0->id, 'type' => 'course', 'referenceid' => $course0->id]);
        $item0h = $generator->create_item(['sectionid' => $section0h->id, 'type' => 'course', 'referenceid' => $course0h->id]);
        $item1 = $generator->create_item(['sectionid' => $section1->id, 'type' => 'course', 'referenceid' => $course1->id]);

        $collection = $generator->create_collection(['uservisible' => 1]);
        $collection1 = $generator->create_collection(['uservisible' => 1, 'contextid' => $catcontext1->id]);
        foreach ([$item0, $item0h, $item1] as $item) {
            $generator->create_collection_item(['collectionid' => $collection->id, 'itemid' => $item->id]);
            $generator->create_collection_item(['collectionid' => $collection1->id, 'itemid' => $item->id]);
        }

        $itemids = fn(int $sectionid): array => $this->get_items(['sectionid' => $sectionid])['itemids'];

        $this->setUser($user0);
        $this->assertSame([(int)$item0->id, (int)$item0h->id], $itemids(0));
        $this->assertSame([(int)$item0h->id], $itemids((int)$section0h->id));
        $this->assertSame([(int)$item0->id, (int)$item0h->id], $itemids(-1 * $collection->id));
        $this->assert_refused(['sectionid' => $section1->id], \core\exception\invalid_parameter_exception::class);
        $this->assert_refused(['sectionid' => -1 * $collection1->id], \core\exception\invalid_parameter_exception::class);

        $this->setUser($user1);
        $this->assertSame([(int)$item0->id, (int)$item1->id], $itemids(0));
        $this->assertSame([(int)$item1->id], $itemids((int)$section1->id));
        $this->assertSame([(int)$item0->id, (int)$item1->id], $itemids(-1 * $collection->id));
        $this->assertSame([(int)$item0->id, (int)$item1->id], $itemids(-1 * $collection1->id));
        $this->assert_refused(['sectionid' => $section0h->id], \core\exception\invalid_parameter_exception::class);

        $this->setUser($user2);
        $this->assertSame([(int)$item0->id], $itemids(0));
        $this->assertSame([(int)$item0->id], $itemids(-1 * $collection->id));
        $this->assert_refused(['sectionid' => $section0h->id], \core\exception\invalid_parameter_exception::class);
        $this->assert_refused(['sectionid' => $section1->id], \core\exception\invalid_parameter_exception::class);
        $this->assert_refused(['sectionid' => -1 * $collection1->id], \core\exception\invalid_parameter_exception::class);
    }
}
