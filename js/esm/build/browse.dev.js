var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
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
import Pending from "@moodle/lms/core/pending";
import { requireAsync } from "@moodle/lms/core/amd";
import { redirect } from "@moodle/lms/core/location";
const SEARCH_DELAY = 1e3;
class Browse {
  static {
    __name(this, "Browse");
  }
  elements;
  itemsUrl;
  browseUrl;
  sectionId;
  storageKey;
  nextLimitFrom;
  hasMore;
  lastSearchValue = "";
  debounceTimer;
  /**
   * @param elements page elements
   */
  constructor(elements) {
    this.elements = elements;
    const dataset = elements.filtersForm.dataset;
    this.itemsUrl = dataset.itemsUrl ?? "";
    this.browseUrl = dataset.browseUrl ?? "";
    this.sectionId = dataset.originalSectionid ?? "0";
    this.storageKey = "tool_mucatalog|browse|" + this.browseUrl;
    this.nextLimitFrom = parseInt(dataset.originalNextlimitfrom ?? "0") || 0;
    this.hasMore = !!dataset.originalHasmore;
  }
  /**
   * Currently selected item type.
   *
   * @returns type name, empty string means all types
   */
  get type() {
    return this.elements.filtersForm.querySelector('[name="type"]:checked')?.value ?? "";
  }
  /**
   * Current search text.
   *
   * @returns trimmed text
   */
  get search() {
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
  getItemsUrl(limitFrom, limitNum, append) {
    const url = new URL(this.itemsUrl, window.location.href);
    url.searchParams.set("sectionid", this.sectionId);
    url.searchParams.set("limitfrom", String(limitFrom));
    url.searchParams.set("limitnum", String(limitNum));
    url.searchParams.set("search", this.search);
    url.searchParams.set("type", this.type);
    url.searchParams.set("append", append ? "1" : "0");
    return url.toString();
  }
  /**
   * Fetch rendered items.
   *
   * @param url items endpoint URL
   * @returns answer of the endpoint
   */
  static async fetchItems(url) {
    const response = await fetch(url, {
      method: "GET",
      headers: { "Accept": "application/json" },
      credentials: "same-origin"
    });
    if (!response.ok) {
      throw new Error(`Catalogue items request failed: ${response.status}`);
    }
    return await response.json();
  }
  /**
   * Run JavaScript required by rendered items.
   *
   * @param javascript collected JavaScript requirements
   */
  static async runJavascript(javascript) {
    if (!javascript) {
      return;
    }
    try {
      const fragment = await requireAsync("core/fragment");
      const templates = await requireAsync("core/templates");
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
  static async showError(error) {
    try {
      const notification = await requireAsync("core/notification");
      notification.exception(error);
    } catch {
      window.console.error(error);
    }
  }
  /**
   * Save current state to sessionStorage.
   */
  saveState() {
    const state = {
      sectionId: this.sectionId,
      nextLimitFrom: this.nextLimitFrom,
      hasMore: this.hasMore,
      scrollY: window.scrollY
    };
    sessionStorage.setItem(this.storageKey, JSON.stringify(state));
  }
  /**
   * Restore state from sessionStorage, this deals with browser Back button.
   */
  restoreState() {
    const { moreButton, itemsContainer } = this.elements;
    const saved = sessionStorage.getItem(this.storageKey);
    if (!saved) {
      return;
    }
    let state;
    try {
      state = JSON.parse(saved);
    } catch {
      return;
    }
    const filtering = this.search !== "" || this.type !== "";
    if (state.sectionId !== this.sectionId) {
      if (filtering) {
        moreButton.disabled = false;
        void this.reloadItems(0);
      }
      return;
    }
    if (this.nextLimitFrom === state.nextLimitFrom && !filtering) {
      return;
    }
    moreButton.disabled = false;
    if (filtering) {
      moreButton.classList.add("hidden");
      itemsContainer.replaceChildren();
    }
    void this.reloadItems(state.nextLimitFrom, state.scrollY);
  }
  /**
   * Update controls after loading of items.
   */
  fixState() {
    const { moreButton, reloadingSpinner, loadingSpinner } = this.elements;
    reloadingSpinner.classList.add("hidden");
    loadingSpinner.classList.add("hidden");
    if (this.hasMore) {
      moreButton.disabled = false;
      moreButton.classList.remove("hidden");
    } else {
      moreButton.classList.add("hidden");
    }
  }
  /**
   * Replace all displayed items.
   *
   * @param limitNum number of items, 0 means default
   * @param scrollY optional scroll position to restore
   */
  async reloadItems(limitNum, scrollY = 0) {
    const { moreButton, reloadingSpinner, loadingSpinner, itemsContainer } = this.elements;
    const pending = new Pending("tool_mucatalog/browse:reload");
    this.nextLimitFrom = 0;
    this.hasMore = false;
    this.lastSearchValue = this.search;
    moreButton.disabled = true;
    loadingSpinner.classList.add("hidden");
    reloadingSpinner.classList.remove("hidden");
    const url = this.getItemsUrl(0, limitNum, false);
    try {
      const answer = await Browse.fetchItems(url);
      if (url !== this.getItemsUrl(0, limitNum, false)) {
        return;
      }
      this.nextLimitFrom = answer.nextlimitfrom;
      this.hasMore = answer.hasmore;
      itemsContainer.innerHTML = answer.html;
      await Browse.runJavascript(answer.javascript);
      this.fixState();
      if (scrollY > 0) {
        const tryScroll = /* @__PURE__ */ __name((attemptsLeft) => {
          window.scrollTo(0, scrollY);
          if (window.scrollY !== scrollY && attemptsLeft > 0) {
            setTimeout(() => tryScroll(attemptsLeft - 1), 50);
          }
        }, "tryScroll");
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
  async loadMoreItems() {
    const { moreButton, loadingSpinner, itemsContainer } = this.elements;
    const pending = new Pending("tool_mucatalog/browse:more");
    moreButton.disabled = true;
    loadingSpinner.classList.remove("hidden");
    const limitFrom = this.nextLimitFrom;
    const url = this.getItemsUrl(limitFrom, 0, true);
    try {
      const answer = await Browse.fetchItems(url);
      if (limitFrom !== this.nextLimitFrom || url !== this.getItemsUrl(limitFrom, 0, true)) {
        return;
      }
      this.nextLimitFrom = answer.nextlimitfrom;
      this.hasMore = answer.hasmore;
      itemsContainer.insertAdjacentHTML("beforeend", answer.html);
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
  handleSearch(value) {
    if (value === this.lastSearchValue) {
      return;
    }
    if (value.length > 1 || value === "") {
      void this.reloadItems(0);
    }
  }
  /**
   * Register event handlers and restore previous state.
   */
  start() {
    const { filtersForm, sectionSelect, searchInput, moreButton } = this.elements;
    window.addEventListener("beforeunload", () => {
      this.saveState();
    });
    moreButton.addEventListener("click", () => {
      void this.loadMoreItems();
    });
    sectionSelect.addEventListener("change", () => {
      const url = new URL(this.browseUrl, window.location.href);
      url.searchParams.set("sectionid", String(parseInt(sectionSelect.value) || 0));
      redirect(url.toString());
    });
    searchInput.addEventListener("input", () => {
      const value = this.search;
      window.clearTimeout(this.debounceTimer);
      if (value === "") {
        this.handleSearch(value);
      } else {
        this.debounceTimer = window.setTimeout(() => this.handleSearch(value), SEARCH_DELAY);
      }
    });
    searchInput.addEventListener("blur", () => {
      window.clearTimeout(this.debounceTimer);
      const value = this.search;
      if (value !== "" && value.length < 2 && value !== this.lastSearchValue) {
        searchInput.value = this.lastSearchValue;
        return;
      }
      this.handleSearch(value);
    });
    searchInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        window.clearTimeout(this.debounceTimer);
        this.handleSearch(this.search);
      }
    });
    filtersForm.querySelectorAll("[data-type-radios]").forEach((group) => {
      group.addEventListener("change", (event) => {
        if (event.target instanceof HTMLInputElement && event.target.type === "radio") {
          void this.reloadItems(0);
        }
      });
    });
    this.restoreState();
  }
}
const init = /* @__PURE__ */ __name(() => {
  const filtersForm = document.getElementById("tool_mucatalog-form-filters");
  const sectionSelect = document.getElementById("tool_mucatalog-sectionid");
  const searchInput = document.getElementById("tool_mucatalog-search");
  const moreButton = document.getElementById("tool_mucatalog-browse-more");
  const reloadingSpinner = document.getElementById("tool_mucatalog-browse-reloading");
  const loadingSpinner = document.getElementById("tool_mucatalog-browse-loading");
  const itemsContainer = document.getElementById("tool_mucatalog-browse-items");
  if (!(filtersForm instanceof HTMLFormElement) || !(sectionSelect instanceof HTMLSelectElement) || !(searchInput instanceof HTMLInputElement) || !(moreButton instanceof HTMLButtonElement) || !reloadingSpinner || !loadingSpinner || !itemsContainer) {
    return;
  }
  const browse = new Browse({
    filtersForm,
    sectionSelect,
    searchInput,
    moreButton,
    reloadingSpinner,
    loadingSpinner,
    itemsContainer
  });
  browse.start();
}, "init");
export {
  init
};
//# sourceMappingURL=browse.dev.js.map
