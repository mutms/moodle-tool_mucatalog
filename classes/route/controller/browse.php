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

namespace tool_mucatalog\route\controller;

use core\exception\invalid_parameter_exception;
use core\param;
use core\router\route;
use core\router\schema\parameters\query_parameter;
use core\url;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use tool_mucatalog\local\catalogue;

/**
 * Universal catalogue browsing page endpoints.
 *
 * @package     tool_mucatalog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class browse {
    use \core\router\route_controller;

    /**
     * Returns rendered items of a section, collection or of the whole catalogue.
     *
     * The answer is JSON with html of the items, JavaScript required by the html
     * and paging information, it is used from the browsing page only.
     *
     * NOTE: not-logged-in users and guests may browse sections visible to guests,
     *       that is why login is not required here unless forced for the whole site.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    #[route(
        path: '/browse/items',
        method: ['GET'],
        queryparams: [
            new query_parameter(name: 'sectionid', type: param::INT, default: 0),
            new query_parameter(name: 'limitfrom', type: param::INT, default: 0),
            new query_parameter(name: 'limitnum', type: param::INT, default: 0),
            new query_parameter(name: 'search', type: param::RAW, default: ''),
            new query_parameter(name: 'type', type: param::ALPHANUMEXT, default: ''),
            // Core accepts only the strings true and false for BOOL query parameters, so this is an INT.
            new query_parameter(name: 'append', type: param::INT, default: 0),
        ],
    )]
    public function items(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        global $CFG, $OUTPUT, $PAGE, $USER;

        $syscontext = \core\context\system::instance();
        $PAGE->set_context($syscontext);
        $PAGE->set_url(new url($request->getUri()->getPath()));

        if (!empty($CFG->forcelogin)) {
            require_login(null, false);
        }
        require_capability('tool/mucatalog:browse', $syscontext);

        $params = $request->getQueryParams();
        $sectionid = (int)($params['sectionid'] ?? 0);
        $limitfrom = (int)($params['limitfrom'] ?? 0);
        $limitnum = (int)($params['limitnum'] ?? 0);
        $search = trim((string)($params['search'] ?? ''));
        $type = clean_param((string)($params['type'] ?? ''), PARAM_ALPHANUMEXT);
        $append = !empty($params['append']);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
        } else {
            $tenantid = null;
        }

        if ($sectionid > 0) {
            if (!catalogue::is_section_visible($sectionid, $USER->id, $tenantid)) {
                throw new invalid_parameter_exception('invalid sectionid parameter');
            }
        } else if ($sectionid < 0) {
            if (!catalogue::is_collection_visible(-1 * $sectionid, $USER->id, $tenantid)) {
                throw new invalid_parameter_exception('invalid sectionid parameter');
            }
        }

        if ($limitfrom < 0) {
            throw new invalid_parameter_exception('invalid limitfrom parameter');
        }
        if ($limitnum < 1) {
            $limitnum = catalogue::ITEMS_PER_PAGE;
        }

        $filters = [
            ['field' => 'search', 'value' => $search],
            ['field' => 'type', 'value' => $type],
        ];

        $hasmore = false;
        $rawitems = catalogue::get_visible_items($sectionid, $USER->id, $tenantid, catalogue::ITEMS_BY_NAME, $limitfrom, $limitnum + 1, $filters);
        if (count($rawitems) > $limitnum) {
            $rawitems = array_slice($rawitems, 0, $limitnum, true);
            $hasmore = true;
        }

        $items = [];
        foreach ($rawitems as $rawitem) {
            $itemdata = \tool_mucatalog\output\browse::format_item_data($rawitem);
            if ($itemdata) {
                $items[] = $itemdata;
            }
        }

        if (!defined('PREFERRED_RENDERER_TARGET')) {
            define('PREFERRED_RENDERER_TARGET', RENDERER_TARGET_GENERAL);
        }
        // Theme initialisation is required before JavaScript can be collected, the page output is not needed.
        $OUTPUT->header();
        $PAGE->start_collecting_javascript_requirements();
        try {
            // Appended items do not include the information that nothing was found.
            $template = $append ? 'tool_mucatalog/browse-more' : 'tool_mucatalog/browse-items';
            $html = $OUTPUT->render_from_template($template, ['items' => $items]);
            $javascript = $PAGE->requires->get_end_code();
        } finally {
            $PAGE->end_collecting_javascript_requirements();
        }

        $payload = [
            'html' => $html,
            'javascript' => $javascript,
            'nextlimitfrom' => $limitfrom + $limitnum,
            'hasmore' => $hasmore,
        ];
        $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
        return $response;
    }
}
