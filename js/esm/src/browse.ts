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
 * Universal catalogue browsing page.
 *
 * Items are rendered on the server, the items endpoint answers with JSON:
 * {html, javascript, nextlimitfrom, hasmore}.
 *
 * @module     tool_mucatalog/browse
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Pending from '@moodle/lms/core/pending';
import {requireAsync} from '@moodle/lms/core/amd';
import {redirect} from '@moodle/lms/core/location';

/** JSON answer of the items endpoint. */
interface Answer {
    html: string;
    javascript: string;
    nextlimitfrom: number;
    hasmore: boolean;
}

/** State stored in sessionStorage to restore the page after browser Back. */
interface SavedState {
    sectionId: string;
    nextLimitFrom: number;
    hasMore: boolean;
    scrollY: number;
}

/** Subset of core/fragment. */
interface Fragment {
    processCollectedJavascript(js: string): string;
}

/** Subset of core/templates. */
interface Templates {
    runTemplateJS(js: string): void;
}

/** Subset of core/notification. */
interface Notification {
    exception(error: unknown): void;
}

/** Elements of the browsing page. */
interface Elements {
    filtersForm: HTMLFormElement;
    sectionSelect: HTMLSelectElement;
    searchInput: HTMLInputElement;
    moreButton: HTMLButtonElement;
    reloadingSpinner: HTMLElement;
    loadingSpinner: HTMLElement;
    itemsContainer: HTMLElement;
}

/** Delay of search after the last key press. */
const SEARCH_DELAY = 1000;

/**
 * Catalogue browsing page with filters and loading of more items.
 */
class Browse {
    private readonly elements: Elements;
    private readonly itemsUrl: string;
    private readonly browseUrl: string;
    private readonly sectionId: string;
    private readonly storageKey: string;
    private nextLimitFrom: number;
    private hasMore: boolean;
    private lastSearchValue = '';
    private debounceTimer: number | undefined;

    /**
     * @param elements page elements
     */
    constructor(elements: Elements) {
        this.elements = elements;
        const dataset = elements.filtersForm.dataset;
        this.itemsUrl = dataset.itemsUrl ?? '';
        this.browseUrl = dataset.browseUrl ?? '';
        this.sectionId = dataset.originalSectionid ?? '0';
        this.storageKey = 'tool_mucatalog|browse|' + this.browseUrl;
        this.nextLimitFrom = parseInt(dataset.originalNextlimitfrom ?? '0') || 0;
        this.hasMore = !!dataset.originalHasmore;
    }

    /**
     * Currently selected item type.
     *
     * @returns type name, empty string means all types
     */
    private get type(): string {
        return this.elements.filtersForm.querySelector<HTMLInputElement>('[name="type"]:checked')?.value ?? '';
    }

    /**
     * Current search text.
     *
     * @returns trimmed text
     */
    private get search(): string {
        return this.elements.searchInput.value.trim();
    }

    /**
     * Returns items endpoint URL for current filters.
     *
     * @param limitFrom first item
     * @param limitNum number of items, 0 means default
     * @param append true when items are added to the already displayed items
     * @returns URL
     */
    private getItemsUrl(limitFrom: number, limitNum: number, append: boolean): string {
        const url = new URL(this.itemsUrl, window.location.href);
        url.searchParams.set('sectionid', this.sectionId);
        url.searchParams.set('limitfrom', String(limitFrom));
        url.searchParams.set('limitnum', String(limitNum));
        url.searchParams.set('search', this.search);
        url.searchParams.set('type', this.type);
        url.searchParams.set('append', append ? '1' : '0');
        return url.toString();
    }

    /**
     * Fetch rendered items.
     *
     * @param url items endpoint URL
     * @returns answer of the endpoint
     */
    private static async fetchItems(url: string): Promise<Answer> {
        const response = await fetch(url, {
            method: 'GET',
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error(`Catalogue items request failed: ${response.status}`);
        }
        return await response.json() as Answer;
    }

    /**
     * Run JavaScript required by rendered items.
     *
     * @param javascript collected JavaScript requirements
     */
    private static async runJavascript(javascript: string): Promise<void> {
        if (!javascript) {
            return;
        }
        try {
            const fragment = await requireAsync<Fragment>('core/fragment');
            const templates = await requireAsync<Templates>('core/templates');
            templates.runTemplateJS(fragment.processCollectedJavascript(javascript));
        } catch (error) {
            window.console.error(error);
        }
    }

    /**
     * Show error.
     *
     * @param error the problem
     */
    private static async showError(error: unknown): Promise<void> {
        try {
            const notification = await requireAsync<Notification>('core/notification');
            notification.exception(error);
        } catch {
            window.console.error(error);
        }
    }

    /**
     * Save current state to sessionStorage.
     */
    private saveState(): void {
        const state: SavedState = {
            sectionId: this.sectionId,
            nextLimitFrom: this.nextLimitFrom,
            hasMore: this.hasMore,
            scrollY: window.scrollY,
        };
        sessionStorage.setItem(this.storageKey, JSON.stringify(state));
    }

    /**
     * Restore state from sessionStorage, this deals with browser Back button.
     */
    private restoreState(): void {
        const {moreButton, itemsContainer} = this.elements;
        const saved = sessionStorage.getItem(this.storageKey);
        if (!saved) {
            return;
        }

        let state: SavedState;
        try {
            state = JSON.parse(saved) as SavedState;
        } catch {
            return;
        }
        const filtering = (this.search !== '' || this.type !== '');

        if (state.sectionId !== this.sectionId) {
            // Must be multiple catalogues open at the same time.
            if (filtering) {
                moreButton.disabled = false;
                void this.reloadItems(0);
            }
            return;
        }

        if (this.nextLimitFrom === state.nextLimitFrom && !filtering) {
            // Form was not changed, this is initial load or Back button in browser to unmodified page.
            return;
        }

        moreButton.disabled = false;
        if (filtering) {
            moreButton.classList.add('hidden');
            itemsContainer.replaceChildren();
        }

        void this.reloadItems(state.nextLimitFrom, state.scrollY);
    }

    /**
     * Update controls after loading of items.
     */
    private fixState(): void {
        const {moreButton, reloadingSpinner, loadingSpinner} = this.elements;
        reloadingSpinner.classList.add('hidden');
        loadingSpinner.classList.add('hidden');

        if (this.hasMore) {
            moreButton.disabled = false;
            moreButton.classList.remove('hidden');
        } else {
            moreButton.classList.add('hidden');
        }
    }

    /**
     * Replace all displayed items.
     *
     * @param limitNum number of items, 0 means default
     * @param scrollY optional scroll position to restore
     */
    private async reloadItems(limitNum: number, scrollY = 0): Promise<void> {
        const {moreButton, reloadingSpinner, loadingSpinner, itemsContainer} = this.elements;
        const pending = new Pending('tool_mucatalog/browse:reload');

        this.nextLimitFrom = 0;
        this.hasMore = false;
        this.lastSearchValue = this.search;

        moreButton.disabled = true;
        loadingSpinner.classList.add('hidden');
        reloadingSpinner.classList.remove('hidden');

        const url = this.getItemsUrl(0, limitNum, false);
        try {
            const answer = await Browse.fetchItems(url);
            if (url !== this.getItemsUrl(0, limitNum, false)) {
                // Filters changed in the meantime, do not replace anything!
                return;
            }
            this.nextLimitFrom = answer.nextlimitfrom;
            this.hasMore = answer.hasmore;
            itemsContainer.innerHTML = answer.html;
            await Browse.runJavascript(answer.javascript);
            this.fixState();
            if (scrollY > 0) {
                const tryScroll = (attemptsLeft: number): void => {
                    window.scrollTo(0, scrollY);
                    if (window.scrollY !== scrollY && attemptsLeft > 0) {
                        setTimeout(() => tryScroll(attemptsLeft - 1), 50);
                    }
                };
                setTimeout(() => tryScroll(5), 0);
            }
        } catch (error) {
            await Browse.showError(error);
        } finally {
            pending.resolve();
        }
    }

    /**
     * Add more items after the displayed items.
     */
    private async loadMoreItems(): Promise<void> {
        const {moreButton, loadingSpinner, itemsContainer} = this.elements;
        const pending = new Pending('tool_mucatalog/browse:more');

        moreButton.disabled = true;
        loadingSpinner.classList.remove('hidden');

        const limitFrom = this.nextLimitFrom;
        const url = this.getItemsUrl(limitFrom, 0, true);
        try {
            const answer = await Browse.fetchItems(url);
            if (limitFrom !== this.nextLimitFrom || url !== this.getItemsUrl(limitFrom, 0, true)) {
                // Filters changed in the meantime, do not append anything!
                return;
            }
            this.nextLimitFrom = answer.nextlimitfrom;
            this.hasMore = answer.hasmore;
            itemsContainer.insertAdjacentHTML('beforeend', answer.html);
            await Browse.runJavascript(answer.javascript);
            this.fixState();
        } catch (error) {
            await Browse.showError(error);
        } finally {
            pending.resolve();
        }
    }

    /**
     * Search after the text was changed.
     *
     * @param value trimmed search text
     */
    private handleSearch(value: string): void {
        if (value === this.lastSearchValue) {
            return;
        }
        if (value.length > 1 || value === '') {
            void this.reloadItems(0);
        }
    }

    /**
     * Register event handlers and restore previous state.
     */
    public start(): void {
        const {filtersForm, sectionSelect, searchInput, moreButton} = this.elements;

        window.addEventListener('beforeunload', () => {
            this.saveState();
        });

        moreButton.addEventListener('click', () => {
            void this.loadMoreItems();
        });

        sectionSelect.addEventListener('change', () => {
            const url = new URL(this.browseUrl, window.location.href);
            url.searchParams.set('sectionid', String(parseInt(sectionSelect.value) || 0));
            redirect(url.toString());
        });

        searchInput.addEventListener('input', () => {
            const value = this.search;
            window.clearTimeout(this.debounceTimer);
            if (value === '') {
                this.handleSearch(value);
            } else {
                this.debounceTimer = window.setTimeout(() => this.handleSearch(value), SEARCH_DELAY);
            }
        });
        searchInput.addEventListener('blur', () => {
            window.clearTimeout(this.debounceTimer);
            const value = this.search;
            if (value !== '' && value.length < 2 && value !== this.lastSearchValue) {
                searchInput.value = this.lastSearchValue;
                return;
            }
            this.handleSearch(value);
        });
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                window.clearTimeout(this.debounceTimer);
                this.handleSearch(this.search);
            }
        });

        filtersForm.querySelectorAll('[data-type-radios]').forEach((group) => {
            group.addEventListener('change', (event) => {
                if (event.target instanceof HTMLInputElement && event.target.type === 'radio') {
                    void this.reloadItems(0);
                }
            });
        });

        this.restoreState();
    }
}

/**
 * Initialise the browsing page.
 */
export const init = (): void => {
    const filtersForm = document.getElementById('tool_mucatalog-form-filters');
    const sectionSelect = document.getElementById('tool_mucatalog-sectionid');
    const searchInput = document.getElementById('tool_mucatalog-search');
    const moreButton = document.getElementById('tool_mucatalog-browse-more');
    const reloadingSpinner = document.getElementById('tool_mucatalog-browse-reloading');
    const loadingSpinner = document.getElementById('tool_mucatalog-browse-loading');
    const itemsContainer = document.getElementById('tool_mucatalog-browse-items');

    if (
        !(filtersForm instanceof HTMLFormElement)
        || !(sectionSelect instanceof HTMLSelectElement)
        || !(searchInput instanceof HTMLInputElement)
        || !(moreButton instanceof HTMLButtonElement)
        || !reloadingSpinner
        || !loadingSpinner
        || !itemsContainer
    ) {
        return;
    }

    const browse = new Browse({
        filtersForm,
        sectionSelect,
        searchInput,
        moreButton,
        reloadingSpinner,
        loadingSpinner,
        itemsContainer,
    });
    browse.start();
};
