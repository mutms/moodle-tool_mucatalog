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

/**
 * JavaScript for Universal catalogue browsing.
 *
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';
import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';

let filtersForm;
let sectionSelect;
let searchInput;
let moreButton;
let reloadingSpinner;
let loadingSpinner;
let itemsContainer;

let nextLimitFrom;
let hasMore;

let debounceTimer;
let lastSearchValue = '';

const STORAGE_KEY = 'tool_mucatalog|browse|' + Config.wwwroot;

/**
 * Returns items page url.
 *
 * @param {number} sectionId
 * @return {string}
 */
const getBrowseUrl = (sectionId) => {
    return Config.wwwroot + '/admin/tool/mucatalog/?sectionid=' + sectionId;
};

/**
 * Returns arguments for get_items ajax call.
 * @param {number} limitNum
 * @return {Object}
 */
const getAjaxArgs = (limitNum) => {
    return {
        sectionid: parseInt(filtersForm.dataset.originalSectionid),
        orderby: 'name',
        limitfrom: nextLimitFrom,
        limitnum: limitNum,
        filters: [
            {field: 'search', value: searchInput.value.trim()},
            {field: 'type', value: filtersForm.querySelector('[name="type"]:checked')?.value || ''},
        ],
    };
};

/**
 * Save current filters state to sessionStorage.
 */
const saveState = () => {
    const state = {
        sectionId: filtersForm.dataset.originalSectionid,
        nextLimitFrom: nextLimitFrom,
        hasMore: hasMore,
        scrollY: window.scrollY,
    };

    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
};

/**
 * Restore filters state from sessionStorage.
 */
const restoreState = () => {
    const saved = sessionStorage.getItem(STORAGE_KEY);
    if (!saved) {
        return;
    }

    const state = JSON.parse(saved);

    if (state.sectionId !== filtersForm.dataset.originalSectionid) {
        // Must be multiple catalogues open at the same time.
        if (searchInput.value !== '' || filtersForm.querySelector('[name="type"]:checked')?.value !== '') {
            moreButton.disabled = false;
            reloadItems(0);
        }
        return;
    }

    if (nextLimitFrom === state.nextLimitFrom
        && searchInput.value === ''
        && filtersForm.querySelector('[name="type"]:checked')?.value === ''
    ) {
        // Form was not changed, this is initial load or Back button in browser to unmodified page.
        return;
    }

    moreButton.disabled = false;
    if (searchInput.value !== '' || filtersForm.querySelector('[name="type"]:checked')?.value !== '') {
        moreButton.classList.add('hidden');
        itemsContainer.replaceChildren();
    }

    reloadItems(state.nextLimitFrom, state.scrollY);
};

/**
 * Enable all controls.
 */
const fixState = () => {
    reloadingSpinner.classList.add('hidden');
    loadingSpinner.classList.add('hidden');

    if (hasMore) {
        moreButton.disabled = false;
        moreButton.classList.remove('hidden');
    } else {
        moreButton.classList.add('hidden');
    }
};

/**
 * Reload the items.
 * @param {number} limitNum
 * @param {number} scrollY optional scroll to
 */
const reloadItems = (limitNum, scrollY) => {
    nextLimitFrom = 0;
    hasMore = 0;

    let ajaxArgs = getAjaxArgs(limitNum);

    lastSearchValue = searchInput.value.trim();

    moreButton.disabled = true;
    loadingSpinner.classList.add('hidden');
    reloadingSpinner.classList.remove('hidden');

    const requests = Ajax.call([{
        methodname: 'tool_mucatalog_get_items',
        args: ajaxArgs,
        loginrequired: true, // Confusing param name - we want sessions in WS, but we need to call normal endpoint.
    }]);

    requests[0].done(function(response) {
        if (JSON.stringify(ajaxArgs) !== JSON.stringify(getAjaxArgs(limitNum))) {
            // Something changed, no not replace anything!
            return;
        }
        nextLimitFrom = parseInt(response.nextlimitfrom);
        hasMore = parseInt(response.hasmore);
        Templates.render('tool_mucatalog/browse-items', response).done((html, js) => {
            Templates.replaceNodeContents(itemsContainer, html, js);
            fixState();
            if (scrollY > 0) {
                const tryScroll = (attemptsLeft) => {
                    window.scrollTo(0, scrollY);
                    if (window.scrollY !== scrollY && attemptsLeft > 0) {
                        setTimeout(() => tryScroll(attemptsLeft - 1), 50);
                    }
                };
                setTimeout(() => tryScroll(5), 0);
            }
        });

    }).fail(Notification.exception);
};

/**
 * Load more items.
 */
const loadMoreItems = () => {
    let ajaxArgs = getAjaxArgs(0);

    moreButton.disabled = true;
    loadingSpinner.classList.remove('hidden');

    const requests = Ajax.call([{
        methodname: 'tool_mucatalog_get_items',
        args: ajaxArgs,
        loginrequired: true, // Confusing param name - we want sessions in WS, but we need to call normal endpoint.
    }]);

    requests[0].done(function(response) {
        if (JSON.stringify(ajaxArgs) !== JSON.stringify(getAjaxArgs(0))) {
            // Something changed, no not append anything!
            return;
        }
        nextLimitFrom = parseInt(response.nextlimitfrom);
        hasMore = parseInt(response.hasmore);
        Templates.render('tool_mucatalog/browse-more', response).done((html, js) => {
            Templates.appendNodeContents(itemsContainer, html, js);
            fixState();
        });

    }).fail(Notification.exception);
};

/**
 * Register event handlers.
 */
const registerHandlers = () => {
    window.addEventListener('beforeunload', () => {
        saveState();
    });

    moreButton.addEventListener('click', () => {
        loadMoreItems();
    });

    sectionSelect.addEventListener('change', function() {
        window.location = getBrowseUrl(parseInt(sectionSelect.value));
    });

    const handleSearch = (value) => {
        if (value.length > 1 && value !== lastSearchValue) {
            reloadItems(0);
        }
        if (value === '' && value !== lastSearchValue) {
            reloadItems(0);
        }
    };

    searchInput.addEventListener('input', function(event) {
        const value = event.target.value.trim();
        clearTimeout(debounceTimer);
        if (value === '') {
            handleSearch(value);
        } else {
            debounceTimer = setTimeout(() => handleSearch(value), 1000);
        }
    });
    searchInput.addEventListener('blur', function(event) {
        clearTimeout(debounceTimer);
        const value = event.target.value.trim();
        if (value !== '' && value.length < 2 && value !== lastSearchValue) {
            searchInput.value = lastSearchValue;
            return;
        }
        handleSearch(value);
    });
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            handleSearch(event.target.value.trim());
        }
    });

    document.querySelectorAll('[data-type-radios]').forEach(group => {
        group.addEventListener('change', function(event) {
            if (event.target.type === 'radio') {
                reloadItems(0);
            }
        });
    });
};

/**
 * Initialise items page
 */
export const init = () => {
    filtersForm = document.getElementById('tool_mucatalog-form-filters');
    sectionSelect = document.getElementById('tool_mucatalog-sectionid');
    searchInput = document.getElementById('tool_mucatalog-search');
    moreButton = document.getElementById('tool_mucatalog-browse-more');
    reloadingSpinner = document.getElementById('tool_mucatalog-browse-reloading');
    loadingSpinner = document.getElementById('tool_mucatalog-browse-loading');
    itemsContainer = document.getElementById('tool_mucatalog-browse-items');

    nextLimitFrom = parseInt(filtersForm.dataset.originalNextlimitfrom);
    hasMore = parseInt(filtersForm.dataset.originalHasmore);

    registerHandlers();

    restoreState();
};
