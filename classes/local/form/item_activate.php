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

namespace tool_mucatalog\local\form;

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Activate draft item.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_activate extends form {
    #[\Override]
    protected function definition(): void {
        $section = $this->get_extra_data()['section'];
        $classname = \tool_mucatalog\local\item::get_type_classname($this->get_current_data()['type']);

        $this->add(new info('sectionname', get_string('section_name', 'tool_mucatalog'), $section->name));

        $this->add(new info('typename', get_string('item_type', 'tool_mucatalog'), $classname ? $classname::get_type_name() : get_string('error')));

        $this->add(new info('name', get_string('item_name', 'tool_mucatalog')));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('item_activate', 'tool_mucatalog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
