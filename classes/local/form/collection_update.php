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

use tool_mucatalog\muform\autocompletemany\collection_cohortvisible;
use tool_mulib\local\mulib;
use tool_mulib\muform\element\autocompletemany;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\number;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;
use tool_mulib\muform\validator\required_if_visible;

/**
 * Update a collection.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection_update extends form {
    #[\Override]
    protected function definition(): void {
        $context = $this->get_extra_data()['context'];

        $name = new text('name', get_string('collection_name', 'tool_mucatalog'), ['maxlength' => 254]);
        $name->set_required(true);
        $this->add($name);

        $shortdescription = new textarea('shortdescription', get_string('shortdescription', 'tool_mucatalog'), ['type' => 'rawtext', 'rows' => 3]);
        $shortdescription->set_required(true);
        $this->add($shortdescription);

        $this->add(new checkbox('frontpageshow', get_string('frontpageshow', 'tool_mucatalog')));

        $frontpagepriority = new number('frontpagepriority', get_string('frontpagepriority', 'tool_mucatalog'));
        $frontpagepriority->set_required_marker(true);
        $frontpagepriority->add_validator(new required_if_visible());
        $this->add($frontpagepriority);
        $this->get_display_manager()->hide_if('frontpagepriority', 'frontpageshow', 'notchecked');

        $this->add(new checkbox('guestvisible', get_string('guestvisible', 'tool_mucatalog')));

        $this->add(new checkbox('uservisible', get_string('uservisible', 'tool_mucatalog')));

        $source = new collection_cohortvisible((int)$this->get_current_data()['id'], 0);
        $this->add(new autocompletemany('cohortvisible', get_string('cohortvisible', 'tool_mucatalog'), $source));
        $this->get_display_manager()->hide_if('cohortvisible', 'uservisible', 'checked');

        if (mulib::is_mutenancy_active() && !$context->tenantid) {
            $this->add(new checkbox('hiddenfromtenants', get_string('hiddenfromtenants', 'tool_mucatalog')));
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('collection_update', 'tool_mucatalog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        // Priority 0 is reserved for "All items".
        if (!empty($data['frontpageshow']) && $data['frontpagepriority'] === 0) {
            $allerrors['frontpagepriority'][] = get_string('error');
        }
    }
}
