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

/**
 * Select item type to add to catalogue.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class items_create_type extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $section = $this->_customdata['section'];
        /** @var class-string<\tool_mucatalog\local\item>[] $types */
        $types = $this->_customdata['types'];

        $mform->addElement('static', 'staticname', get_string('section_name', 'tool_mucatalog'), format_string($section->name));

        $radios = [];
        foreach ($types as $type => $typeclassname) {
            $radios[] = $mform->createElement('radio', 'type', '', $typeclassname::get_type_name(), $type);
        }
        $mform->addElement('group', 'type_group', get_string('item_type', 'tool_mucatalog'), $radios, '<div class="w-100" />', false);
        $mform->addRule('type_group', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'sectionid');
        $mform->setType('sectionid', PARAM_INT);
        $mform->setDefault('sectionid', $section->id);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['type'])) {
            $errors['type_group'] = get_string('required');
        }

        return $errors;
    }
}
