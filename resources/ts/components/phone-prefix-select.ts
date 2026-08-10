/**
 * PhonePrefixSelect — searchable phone country-code picker with flag emoji.
 *
 * Wraps an existing phone input group that has a sibling hidden input for the
 * prefix.  Renders a trigger button (flag + dial code) plus a searchable
 * dropdown.  Searching by country name ("ita", "italia"), dial code ("+39"),
 * or ISO2 code ("IT") all work.
 *
 * Required HTML structure (data attributes drive wiring):
 *
 *   <div data-phone-prefix-wrap>
 *     <input type="hidden" name="phone_prefix"
 *            data-phone-prefix
 *            data-current-value="{{ $person->phone_prefix }}">
 *     <input type="text" name="phone" data-phone-number class="form-input" ...>
 *   </div>
 *
 * window.COUNTRIES_MAP  (iso2 → name_it)  must already be set.
 * window.COUNTRIES_DIAL (iso2 → dial_code) must be set by the Blade view.
 */

declare global {
    interface Window {
        COUNTRIES_DIAL?: Record<string, string>;
    }
}

interface PrefixOption {
    iso2: string;
    name: string;
    dial: string;
    flag: string;
}

/** Convert ISO 3166-1 alpha-2 code to a flag emoji via Unicode regional indicators. */
function iso2ToFlag(iso2: string): string {
    return [...iso2.toUpperCase()]
        .map((c) => String.fromCodePoint(0x1f1e6 + c.charCodeAt(0) - 65))
        .join('');
}

const MAX_LIST = 120;

/** ISO2 auto-selected when the guest starts typing a number without picking a country first. */
const DEFAULT_ISO2 = 'IT';

class PhonePrefixSelect {
    private readonly wrap: HTMLElement;
    private readonly phoneInput: HTMLInputElement;
    private readonly prefixHidden: HTMLInputElement;

    private readonly trigger: HTMLButtonElement;
    private readonly dropdown: HTMLDivElement;
    private readonly searchEl: HTMLInputElement;
    private readonly list: HTMLUListElement;

    private options: PrefixOption[] = [];
    private selected: PrefixOption | null = null;

    constructor(
        wrap: HTMLElement,
        phoneInput: HTMLInputElement,
        prefixHidden: HTMLInputElement,
    ) {
        this.wrap        = wrap;
        this.phoneInput  = phoneInput;
        this.prefixHidden = prefixHidden;

        // ── Trigger button ──────────────────────────────────────────────────
        this.trigger = document.createElement('button');
        this.trigger.type = 'button';
        this.trigger.className = 'phone-prefix__trigger';
        this.trigger.setAttribute('aria-haspopup', 'listbox');
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.setAttribute('tabindex', '0');

        // ── Dropdown ────────────────────────────────────────────────────────
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'phone-prefix__dropdown';
        this.dropdown.hidden = true;
        this.dropdown.setAttribute('role', 'dialog');

        this.searchEl = document.createElement('input');
        this.searchEl.type = 'text';
        this.searchEl.className = 'phone-prefix__search';
        this.searchEl.placeholder = 'Cerca paese o prefisso…';
        this.searchEl.autocomplete = 'off';
        this.searchEl.setAttribute('aria-label', 'Cerca paese');

        this.list = document.createElement('ul');
        this.list.className = 'phone-prefix__list';
        this.list.setAttribute('role', 'listbox');

        this.dropdown.appendChild(this.searchEl);
        this.dropdown.appendChild(this.list);

        // ── Inject into DOM ─────────────────────────────────────────────────
        // Wrap trigger + dropdown + phone input inside a flex container
        wrap.insertBefore(this.trigger, phoneInput);
        wrap.insertBefore(this.dropdown, phoneInput);

        phoneInput.classList.add('phone-prefix__number');

        this._bindEvents();
    }

    setOptions(options: PrefixOption[], initialDial: string = ''): void {
        this.options = options;
        const match = initialDial
            ? options.find((o) => o.dial === initialDial)
            : null;
        this._commit(match ?? null);
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private _commit(opt: PrefixOption | null): void {
        this.selected = opt;
        this.prefixHidden.value = opt?.dial ?? '';
        this._updateTrigger();
        this._close();
    }

    private _updateTrigger(): void {
        if (this.selected) {
            this.trigger.innerHTML =
                `<span class="phone-prefix__flag">${this.selected.flag}</span>` +
                `<span class="phone-prefix__code">${this.selected.dial}</span>` +
                `<span class="phone-prefix__chevron">▾</span>`;
            this.trigger.setAttribute('aria-label', `Prefisso: ${this.selected.name} ${this.selected.dial}`);
        } else {
            this.trigger.innerHTML =
                `<span class="phone-prefix__placeholder">🌐</span>` +
                `<span class="phone-prefix__chevron">▾</span>`;
            this.trigger.setAttribute('aria-label', 'Seleziona prefisso');
        }
    }

    private _open(): void {
        this.dropdown.hidden = false;
        this.trigger.setAttribute('aria-expanded', 'true');
        this._renderList('');
        requestAnimationFrame(() => this.searchEl.focus());
    }

    private _close(): void {
        this.dropdown.hidden = true;
        this.trigger.setAttribute('aria-expanded', 'false');
        this.searchEl.value = '';
    }

    private _renderList(query: string): void {
        const q = query.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        const normalize = (s: string): string =>
            s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        const filtered = q
            ? this.options.filter(
                (o) =>
                    normalize(o.name).includes(q) ||
                    o.dial.replace('+', '').startsWith(q.replace('+', '')) ||
                    o.iso2.toLowerCase().startsWith(q),
            )
            : this.options;

        this.list.innerHTML = '';

        if (filtered.length === 0) {
            const li = document.createElement('li');
            li.className = 'phone-prefix__no-results';
            li.textContent = 'Nessun risultato';
            this.list.appendChild(li);
            return;
        }

        filtered.slice(0, MAX_LIST).forEach((opt) => {
            const li = document.createElement('li');
            li.className =
                'phone-prefix__item' +
                (opt.iso2 === this.selected?.iso2 ? ' phone-prefix__item--selected' : '');
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', opt.iso2 === this.selected?.iso2 ? 'true' : 'false');
            li.dataset.iso2 = opt.iso2;
            li.innerHTML =
                `<span class="phone-prefix__flag">${opt.flag}</span>` +
                `<span class="phone-prefix__item-name">${opt.name}</span>` +
                `<span class="phone-prefix__item-code">${opt.dial}</span>`;
            this.list.appendChild(li);
        });
    }

    private _bindEvents(): void {
        // Toggle dropdown on trigger click
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.dropdown.hidden ? this._open() : this._close();
        });

        // Guard against submitting a phone number without a prefix: if the guest
        // starts typing without ever opening the picker, silently default to IT
        // instead of leaving prefixHidden empty (incomplete "phone" data).
        this.phoneInput.addEventListener('input', () => {
            if (this.selected || this.phoneInput.value.trim() === '') return;
            const fallback = this.options.find((o) => o.iso2 === DEFAULT_ISO2);
            if (fallback) this._commit(fallback);
        });

        // Filter list on search input
        this.searchEl.addEventListener('input', () => {
            this._renderList(this.searchEl.value);
        });

        // Pick item on click (mousedown to fire before blur)
        this.list.addEventListener('mousedown', (e) => {
            e.preventDefault();
            const li = (e.target as HTMLElement).closest<HTMLLIElement>('.phone-prefix__item');
            if (!li?.dataset.iso2) return;
            const opt = this.options.find((o) => o.iso2 === li.dataset.iso2);
            if (opt) {
                this._commit(opt);
                this.phoneInput.focus();
            }
        });

        // Keyboard navigation inside the search field
        this.searchEl.addEventListener('keydown', (e: KeyboardEvent) => {
            const items = Array.from(
                this.list.querySelectorAll<HTMLLIElement>('.phone-prefix__item'),
            );
            if (e.key === 'Escape') {
                this._close();
                this.trigger.focus();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                (items[0] as HTMLElement | undefined)?.focus();
            } else if (e.key === 'Enter' && items.length > 0) {
                e.preventDefault();
                const first = items[0];
                const opt = this.options.find((o) => o.iso2 === first.dataset.iso2);
                if (opt) {
                    this._commit(opt);
                    this.phoneInput.focus();
                }
            }
        });

        // Keyboard navigation within the list items
        this.list.addEventListener('keydown', (e: KeyboardEvent) => {
            const items = Array.from(
                this.list.querySelectorAll<HTMLLIElement>('.phone-prefix__item'),
            );
            const target = e.target as HTMLElement;
            const idx = items.indexOf(target as HTMLLIElement);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                (items[idx + 1] as HTMLElement | undefined)?.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (idx <= 0) {
                    this.searchEl.focus();
                } else {
                    (items[idx - 1] as HTMLElement | undefined)?.focus();
                }
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const opt = this.options.find((o) => o.iso2 === target.dataset.iso2);
                if (opt) {
                    this._commit(opt);
                    this.phoneInput.focus();
                }
            } else if (e.key === 'Escape') {
                this._close();
                this.trigger.focus();
            }
        });

        // Make list items focusable for keyboard nav
        this.list.addEventListener('mousedown', () => {/* handled above */});

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.wrap.contains(e.target as Node)) {
                this._close();
            }
        });
    }
}

/** Build the unified options list from window globals. */
function buildPrefixOptions(): PrefixOption[] {
    const dialMap = window.COUNTRIES_DIAL ?? {};
    const nameMap = window.COUNTRIES_MAP ?? {};

    return Object.entries(dialMap)
        .filter(([, dial]) => !!dial)
        .map(([iso2, dial]) => ({
            iso2,
            name: nameMap[iso2] ?? iso2,
            dial: dial as string,
            flag: iso2ToFlag(iso2),
        }))
        .sort((a, b) => a.name.localeCompare(b.name, 'it'));
}

/**
 * Initialise all phone prefix pickers found in the page.
 * Must run after COUNTRIES_MAP and COUNTRIES_DIAL are set on window.
 */
export function initPhonePrefixSelects(): void {
    const options = buildPrefixOptions();
    if (options.length === 0) return;

    document.querySelectorAll<HTMLElement>('[data-phone-prefix-wrap]').forEach((wrap) => {
        const phoneInput   = wrap.querySelector<HTMLInputElement>('[data-phone-number]');
        const prefixHidden = wrap.querySelector<HTMLInputElement>('[data-phone-prefix]');
        if (!phoneInput || !prefixHidden) return;

        const currentDial = prefixHidden.dataset.currentValue ?? '';
        const ps = new PhonePrefixSelect(wrap, phoneInput, prefixHidden);
        ps.setOptions(options, currentDial);
    });
}
