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
use tool_mulib\muform\element\radios;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Select item type to add to catalogue.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class items_create_type extends form {
    #[\Override]
    protected function definition(): void {
        $section = $this->get_extra_data()['section'];
        /** @var class-string<\tool_mucatalog\local\item>[] $types */
        $types = $this->get_extra_data()['types'];

        $this->add(new info('sectionname', get_string('section_name', 'tool_mucatalog'), $section->name));

        $options = [];
        foreach ($types as $type => $typeclassname) {
            $options[$type] = $typeclassname::get_type_name();
        }
        $type = new radios('type', get_string('item_type', 'tool_mucatalog'), $options);
        $type->set_required(true);
        $this->add($type);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('continue')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
