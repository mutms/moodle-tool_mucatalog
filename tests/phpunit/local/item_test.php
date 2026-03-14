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

use tool_mucatalog\local\item;

/**
 * Catalogue item test.
 *
 * @group       MuTMS
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucatalog\local\item
 */
final class item_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type_classnames(): void {
        $typeclasses = item::get_type_classnames();
        foreach ($typeclasses as $type => $classname) {
            $this->assertTrue(class_exists($classname));
            $this->assertSame($type, $classname::get_type());
            $this->assertSame($type, $classname::TYPE);
        }
    }

    public function test_get_type_classname(): void {
        $this->assertSame(\tool_mucatalog\local\item\course::class, item::get_type_classname('course'));
    }

    public function test_get_type_names(): void {
        $typenames = item::get_type_names();
        foreach ($typenames as $type => $typename) {
            $typeclass = item::get_type_classname($type);
            $this->assertSame($typename, $typeclass::get_type_name());
        }
    }

    public function test_get_create_form_class(): void {
        $this->assertSame(\tool_mucatalog\local\form\items_create::class, item::get_create_form_class());
    }
}
