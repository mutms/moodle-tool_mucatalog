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
use tool_mucatalog\muform\autocomplete\reference_add_sectionid;
use tool_mulib\muform\element\autocomplete;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\radios;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Add course, program or certification to a catalogue section.
 *
 * @package    tool_mucatalog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class reference_add extends form {
    #[\Override]
    protected function definition(): void {
        /** @var class-string<\tool_mucatalog\local\item> $typeclass */
        $typeclass = $this->get_extra_data()['typeclass'];
        $referenceid = (int)$this->get_extra_data()['referenceid'];

        $this->add(new info('typename', get_string('item_type', 'tool_mucatalog'), $typeclass::get_type_name()));

        $this->add(new info('name', get_string('item_name', 'tool_mucatalog')));

        $sectionid = new autocomplete('sectionid', get_string('section', 'tool_mucatalog'), new reference_add_sectionid($typeclass::get_type(), $referenceid));
        $sectionid->set_required(true);
        $this->add($sectionid);

        $this->add(new datetime('hiddenbefore', get_string('hiddenbefore', 'tool_mucatalog')));

        $this->add(new datetime('hiddenafter', get_string('hiddenafter', 'tool_mucatalog')));

        $statuses = util::get_statuses_menu();
        unset($statuses[util::STATUS_ARCHIVED]);
        $status = new radios('status', get_string('item_status', 'tool_mucatalog'), $statuses);
        $status->set_required(true);
        $this->add($status);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('reference_add', 'tool_mucatalog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
