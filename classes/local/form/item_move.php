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

use tool_mucatalog\external\form_autocomplete\item_sectionid;

/**
 * Move item to a different section.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_move extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $item = $this->_customdata['item'];
        $context = $this->_customdata['context'];
        $classname = \tool_mucatalog\local\item::get_type_classname($item->type);

        // TODO: add explanation.

        $mform->addElement('static', 'statictype', get_string('item_type', 'tool_mucatalog'), $classname ? $classname::get_type() : get_string('error'));

        $mform->addElement('static', 'staticname', get_string('item_name', 'tool_mucatalog'), format_string($item->name));

        $args = ['itemid' => $item->id];
        item_sectionid::add_element(
            $mform,
            $args,
            'sectionid',
            get_string('section', 'tool_mucatalog'),
            $context
        );
        $mform->addRule('sectionid', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('item_move', 'tool_mucatalog'));

        $this->set_data($item);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $item = $this->_customdata['item'];
        $context = $this->_customdata['context'];

        if (!$data['sectionid']) {
            $errors['sectionid'] = get_string('required');
        } else {
            $error = item_sectionid::validate_value($data['sectionid'], ['itemid' => $item->id], $context);
            if ($error !== null) {
                $errors['sectionid'] = $error;
            }
        }

        return $errors;
    }
}
