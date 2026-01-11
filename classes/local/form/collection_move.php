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

use tool_mucatalog\external\form_autocomplete\collection_contextid;
use tool_mucatalog\local\util;

/**
 * Move a collection.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection_move extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $currentdata = $this->_customdata['currentdata'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'staticname', get_string('collection_name', 'tool_mucatalog'), format_string($currentdata->name));

        collection_contextid::add_element($mform, [], 'contextid', get_string('collection_category', 'tool_mucatalog'), $context);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('collection_move', 'tool_mucatalog'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $context = $this->_customdata['context'];

        $errors = parent::validation($data, $files);

        $error = collection_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        return $errors;
    }
}
