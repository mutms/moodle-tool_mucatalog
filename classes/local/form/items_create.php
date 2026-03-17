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

use tool_mucatalog\local\util;

/**
 * Add items to catalogue.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class items_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $section = $this->_customdata['section'];
        $context = $this->_customdata['context'];
        $currentdata = $this->_customdata['currentdata'];

        $typeclass = \tool_mucatalog\local\item::get_type_classname($currentdata->type);
        $referencesclass = $typeclass::get_create_form_referenceids_class();

        $mform->addElement('static', 'staticname', get_string('section_name', 'tool_mucatalog'), format_string($section->name));

        $args = ['sectionid' => $section->id];
        $referencesclass::add_element(
            $mform,
            $args,
            'referenceids',
            $referencesclass::get_form_field_name(),
            $context
        );
        $mform->addRule('referenceids', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_time_selector', 'hiddenbefore', get_string('hiddenbefore', 'tool_mucatalog'), ['optional' => true]);

        $mform->addElement('date_time_selector', 'hiddenafter', get_string('hiddenafter', 'tool_mucatalog'), ['optional' => true]);

        $options = util::get_statuses_menu();
        $radios = [];
        foreach ($options as $k => $v) {
            if ($k == util::STATUS_ARCHIVED) {
                continue;
            }
            $radios[] = $mform->createElement('radio', 'status', '', $v, $k);
        }
        $mform->addElement('group', 'statusgroup', get_string('item_status', 'tool_mucatalog'), $radios, '<div class="w-100" />', false);

        $mform->addElement('hidden', 'sectionid');
        $mform->setType('sectionid', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUM);

        $this->add_action_buttons(true, get_string('items_create', 'tool_mucatalog'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $section = $this->_customdata['section'];
        $context = $this->_customdata['context'];
        $currentdata = $this->_customdata['currentdata'];

        $typeclass = \tool_mucatalog\local\item::get_type_classname($currentdata->type);
        $referencesclass = $typeclass::get_create_form_referenceids_class();

        if ($data['referenceids']) {
            foreach ($data['referenceids'] as $referenceid) {
                $error = $referencesclass::validate_value($referenceid, ['sectionid' => $section->id], $context);
                if ($error !== null) {
                    $errors['referenceids'] = $error;
                    break;
                }
            }
        } else {
            $errors['referenceids'] = get_string('required');
        }

        return $errors;
    }
}
