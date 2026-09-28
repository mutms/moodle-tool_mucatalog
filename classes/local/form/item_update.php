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
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\form;
use tool_mulib\muform\validator\required_if_visible;

/**
 * Update item.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_update extends form {
    #[\Override]
    protected function definition(): void {
        $section = $this->get_extra_data()['section'];
        $classname = \tool_mucatalog\local\item::get_type_classname($this->get_current_data()['type']);

        $this->add(new info('sectionname', get_string('section_name', 'tool_mucatalog'), $section->name));

        $this->add(new info('typename', get_string('item_type', 'tool_mucatalog'), $classname::get_type_name()));

        $this->add(new checkbox('syncname', get_string('item_syncname', 'tool_mucatalog')));

        $name = new text('name', get_string('item_name', 'tool_mucatalog'), ['maxlength' => 254]);
        $name->set_required_marker(true);
        $name->add_validator(new required_if_visible());
        $this->add($name);
        $this->get_display_manager()->hide_if('name', 'syncname', 'checked');

        $this->add(new datetime('hiddenbefore', get_string('hiddenbefore', 'tool_mucatalog')));

        $this->add(new datetime('hiddenafter', get_string('hiddenafter', 'tool_mucatalog')));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('item_update', 'tool_mucatalog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
