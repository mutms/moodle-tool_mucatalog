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
 * Update item.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $section = $this->_customdata['section'];
        $context = $this->_customdata['context'];
        $item = $this->_customdata['item'];
        $itemclass = \tool_mucatalog\local\item::get_type_classname($item->type);

        $mform->addElement('static', 'staticname', get_string('section_name', 'tool_mucatalog'), format_string($section->name));

        $mform->addElement('static', 'statictype', get_string('item_type', 'tool_mucatalog'), $itemclass::get_type_name());

        $mform->addElement('advcheckbox', 'syncname', get_string('item_syncname', 'tool_mucatalog'), ' ');

        $mform->addElement('text', 'name', get_string('item_name', 'tool_mucatalog'), 'maxlength="254" size="100"');
        $mform->setType('name', PARAM_TEXT);
        $mform->hideIf('name', 'syncname', 'eq', 1);

        $mform->addElement('date_time_selector', 'hiddenbefore', get_string('hiddenbefore', 'tool_mucatalog'), ['optional' => true]);

        $mform->addElement('date_time_selector', 'hiddenafter', get_string('hiddenafter', 'tool_mucatalog'), ['optional' => true]);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('item_update', 'tool_mucatalog'));

        $this->set_data($item);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['syncname']) {
            if (trim($data['name']) === '') {
                $errors['name'] = get_string('required');
            }
        }

        return $errors;
    }
}
