/**
 * ComboSelect — searchable, locked select component.
 *
 * Wraps an existing <input type="text"> and enhances it with a filtered
 * dropdown list.  In "active" mode the user can only pick a value from the
 * provided options list (locked); the visible input is used for filtering,
 * while a hidden <input> carries the real form value.
 * In "inactive" mode the wrapper is transparent and the original input
 * behaves as a plain text field (free-text allowed).
 *
 * Usage (plain strings — label === value):
 *   const cs = new ComboSelect(inputEl);
 *   cs.enable(stringList, currentValue);
 *   cs.disable();
 *
 * Usage (label/value pairs — e.g. country code ↔ name):
 *   cs.enable([{ label: 'Italia', value: 'IT' }, ...], currentValue);
 */

export interface ComboOption {
    label: string;
    value: string;
}

const MAX_VISIBLE = 100; // max items to render in one go

export class ComboSelect {
    private readonly originalName: string;
    private readonly originalRequired: boolean;

    private readonly wrapper: HTMLDivElement;
    private readonly searchInput: HTMLInputElement;
    private readonly hidden: HTMLInputElement;
    private readonly list: HTMLUListElement;

    private pairs: ComboOption[] = [];
    private committed: string = '';
    private isActive: boolean = false;

    constructor(input: HTMLInputElement) {
        this.searchInput    = input;
        this.originalName     = input.name;
        this.originalRequired = input.required;

        // ── Wrap the original input ────────────────────────────────────────
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'combo-select';
        input.parentNode!.insertBefore(this.wrapper, input);
        this.wrapper.appendChild(input);

        // ── Hidden input carries the real form value in active mode ────────
        this.hidden = document.createElement('input');
        this.hidden.type = 'hidden';
        // name is empty until active mode is enabled
        this.wrapper.appendChild(this.hidden);

        // ── Dropdown list ──────────────────────────────────────────────────
        this.list = document.createElement('ul');
        this.list.className = 'combo-select__list';
        this.list.hidden = true;
        this.wrapper.appendChild(this.list);

        this.committed = input.value;
        this._bindEvents();
    }

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Switch to locked-combobox mode.
     * Accepts either a plain string list (label === value) or an array of
     * {label, value} pairs (e.g. country name ↔ country code).
     */
    enable(options: string[] | ComboOption[], currentValue: string = ''): void {
        this.isActive = true;
        this.pairs    = (options as Array<string | ComboOption>).map((o) =>
            typeof o === 'string' ? { label: o, value: o } : o,
        );

        // Hidden input takes over form submission
        this.searchInput.removeAttribute('name');
        this.hidden.name = this.originalName;

        // Move required from visible to hidden
        this.searchInput.required = false;
        this.hidden.required      = this.originalRequired;

        // Remove any datalist association
        this.searchInput.removeAttribute('list');
        this.searchInput.setAttribute('autocomplete', 'off');

        this._commit(currentValue);
    }

    /** Revert to plain free-text input. */
    disable(): void {
        this.isActive = false;

        // Restore name / required on visible input
        this.searchInput.name     = this.originalName;
        this.searchInput.required = this.originalRequired;

        // Deactivate hidden input
        this.hidden.name     = '';
        this.hidden.value    = '';
        this.hidden.required = false;

        this._hide();
        this.searchInput.setCustomValidity('');
    }

    getValue(): string {
        return this.isActive ? this.hidden.value : this.searchInput.value;
    }

    setValue(val: string): void {
        if (this.isActive) {
            this._commit(val);
        } else {
            this.searchInput.value = val;
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private _bindEvents(): void {
        const si = this.searchInput;

        si.addEventListener('focus', () => {
            if (!this.isActive) return;
            // Select all text so the user can immediately replace / filter
            si.select();
            this._renderList(si.value);
            this._show();
        });

        si.addEventListener('input', () => {
            if (!this.isActive) return;
            si.setCustomValidity('');
            this._renderList(si.value);
            this._show();
        });

        // Close and revert on blur (use a short delay so mousedown on items fires first)
        si.addEventListener('blur', () => {
            if (!this.isActive) return;
            setTimeout(() => {
                this._hide();
                // Accept an exact label match (case-insensitive), otherwise revert display
                const match = this.pairs.find(
                    (p) => p.label.toLowerCase() === si.value.toLowerCase().trim(),
                );
                if (match) {
                    this._commit(match.value);
                } else {
                    // Revert search input to the label of the currently committed value
                    const committed = this.pairs.find((p) => p.value === this.committed);
                    si.value = committed?.label ?? this.committed;
                }
            }, 200);
        });

        si.addEventListener('keydown', (e: KeyboardEvent) => {
            if (!this.isActive) return;
            const items = Array.from(
                this.list.querySelectorAll<HTMLLIElement>('.combo-select__item'),
            );
            const idx = items.findIndex((el) =>
                el.classList.contains('combo-select__item--active'),
            );

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this._setActive(items, idx < 0 ? 0 : Math.min(idx + 1, items.length - 1));
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this._setActive(items, idx < 0 ? items.length - 1 : Math.max(idx - 1, 0));
                    break;
                case 'Enter':
                    if (idx >= 0) {
                        e.preventDefault();
                        this._commit(items[idx].dataset.value!);
                    }
                    break;
                case 'Escape':
                    {
                        const committed = this.pairs.find((p) => p.value === this.committed);
                        si.value = committed?.label ?? this.committed;
                        this._hide();
                    }
                    break;
            }
        });
    }

    private _setActive(items: HTMLLIElement[], newIdx: number): void {
        items.forEach((el) => el.classList.remove('combo-select__item--active'));
        if (newIdx >= 0 && newIdx < items.length) {
            items[newIdx].classList.add('combo-select__item--active');
            items[newIdx].scrollIntoView({ block: 'nearest' });
        }
    }

    private _renderList(filter: string): void {
        const low    = filter.toLowerCase().trim();
        const all    = low ? this.pairs.filter((p) => p.label.toLowerCase().includes(low)) : this.pairs;
        const slice  = all.slice(0, MAX_VISIBLE);
        const extra  = all.length - MAX_VISIBLE;

        this.list.innerHTML = '';

        if (all.length === 0) {
            const li = document.createElement('li');
            li.className   = 'combo-select__no-results';
            li.textContent = 'Nessun risultato';
            this.list.appendChild(li);
            return;
        }

        slice.forEach((opt) => {
            const li = document.createElement('li');
            li.className        = 'combo-select__item';
            li.textContent      = opt.label;
            li.dataset.value    = opt.value;
            li.addEventListener('mousedown', (e) => {
                e.preventDefault(); // prevent blur from firing before commit
                this._commit(opt.value);
            });
            this.list.appendChild(li);
        });

        if (extra > 0) {
            const li = document.createElement('li');
            li.className   = 'combo-select__hint';
            li.textContent = `Scrivi per filtrare (altri ${extra} risultati)`;
            this.list.appendChild(li);
        }
    }

    private _show(): void {
        this.list.hidden = false;
    }

    private _hide(): void {
        this.list.hidden = true;
    }

    private _commit(value: string): void {
        const pair            = this.pairs.find((p) => p.value === value);
        this.committed        = value;
        this.searchInput.value = pair?.label ?? value;
        this.hidden.value      = value;
        // Keep data attribute in sync so external code can read the committed value
        this.searchInput.dataset.comboValue = value;
        this.searchInput.setCustomValidity('');
        this._hide();
        // Notify listeners that the value changed (mirrors native <select> behaviour)
        this.searchInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
