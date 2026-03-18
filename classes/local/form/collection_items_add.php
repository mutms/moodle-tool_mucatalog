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

use tool_mucatalog\external\form_autocomplete\collection_items_add_itemids;

/**
 * Add existing item to collection.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection_items_add extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $collection = $this->_customdata['collection'];
        $context = $this->_customdata['context'];
        $currentdata = $this->_customdata['currentdata'];

        $mform->addElement('static', 'staticcollectionname', get_string('collection_name', 'tool_mucatalog'), format_string($collection->name));

        $args = ['collectionid' => $collection->id];
        collection_items_add_itemids::add_element(
            $mform,
            $args,
            'itemids',
            get_string('items', 'tool_mucatalog'),
            $context
        );
        $mform->addRule('itemids', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'collectionid');
        $mform->setType('collectionid', PARAM_INT);

        $this->add_action_buttons(true, get_string('collection_items_add', 'tool_mucatalog'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $collection = $this->_customdata['collection'];
        $context = $this->_customdata['context'];

        if ($data['itemids']) {
            foreach ($data['itemids'] as $itemid) {
                $error = collection_items_add_itemids::validate_value($itemid, ['collectionid' => $collection->id], $context);
                if ($error !== null) {
                    $errors['itemids'] = $error;
                    break;
                }
            }
        } else {
            $errors['itemids'] = get_string('required');
        }

        return $errors;
    }
}
