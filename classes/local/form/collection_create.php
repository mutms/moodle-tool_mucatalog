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
use tool_mucatalog\external\form_autocomplete\collection_cohortvisible;
use tool_mucatalog\local\util;

/**
 * Add a collection.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class collection_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $currentdata = $this->_customdata['currentdata'];
        $context = $this->_customdata['context'];

        $mform->addElement('text', 'name', get_string('collection_name', 'tool_mucatalog'), 'maxlength="254" size="100"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('textarea', 'shortdescription', get_string('shortdescription', 'tool_mucatalog'), ['rows' => '3', 'cols' => '50']);
        $mform->addRule('shortdescription', get_string('required'), 'required', null, 'client');
        $mform->setType('shortdescription', PARAM_RAW);

        collection_contextid::add_element($mform, [], 'contextid', get_string('collection_category', 'tool_mucatalog'), $context);

        $mform->addElement('advcheckbox', 'frontpageshow', get_string('frontpageshow', 'tool_mucatalog'), ' ');
        if (!empty($currentdata->frontpagepriority)) {
            $mform->setDefault('frontpageshow', 1);
        }

        $mform->addElement('text', 'frontpagepriority', get_string('frontpagepriority', 'tool_mucatalog'));
        $mform->setType('frontpagepriority', PARAM_RAW);
        $mform->hideIf('frontpagepriority', 'frontpageshow', 'noteq', '1');

        $mform->addElement('advcheckbox', 'guestvisible', get_string('guestvisible', 'tool_mucatalog'), ' ');

        $mform->addElement('advcheckbox', 'uservisible', get_string('uservisible', 'tool_mucatalog'), ' ');

        collection_cohortvisible::add_element(
            $mform,
            ['collectionid' => null, 'contextid' => $context->id],
            'cohortvisible',
            get_string('cohortvisible', 'tool_mucatalog'),
            $context
        );
        $mform->hideIf('cohortvisible', 'uservisible', 'eq', 1);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $mform->addElement('advcheckbox', 'hiddenfromtenants', get_string('hiddenfromtenants', 'tool_mucatalog'), ' ');
        }

        $this->add_action_buttons(true, get_string('collection_create', 'tool_mucatalog'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $context = $this->_customdata['context'];

        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }

        if (trim($data['shortdescription']) === '') {
            $errors['shortdescription'] = get_string('required');
        }

        if ($data['frontpageshow']) {
            if (trim($data['frontpagepriority']) === '' || !$data['frontpagepriority']) {
                $errors['frontpagepriority'] = get_string('required');
            } else if (!is_number($data['frontpagepriority'])) {
                $errors['frontpagepriority'] = get_string('error');
            }
        }

        $error = collection_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
            $validatecontext = null;
        } else {
            $validatecontext = \context::instance_by_id($data['contextid']);
        }

        if ($validatecontext && $data['cohortvisible']) {
            $args = ['collectionid' => null, 'contextid' => $context->id];
            foreach ($data['cohortvisible'] as $cohortid) {
                $error = collection_cohortvisible::validate_value($cohortid, $args, $validatecontext);
                if ($error !== null) {
                    $errors['cohortvisible'] = $error;
                    break;
                }
            }
        }

        return $errors;
    }
}
