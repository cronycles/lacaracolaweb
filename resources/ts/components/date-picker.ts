/**
 * Date range picker — Booking.com / Airbnb style.
 * Vanilla TypeScript, no external dependencies.
 * Shows two months on desktop (≥640 px), one month on mobile.
 */

function isSameDay(a: Date, b: Date): boolean {
    return a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate();
}

function startOfDay(d: Date): Date {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

function addDays(d: Date, n: number): Date {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
}

function addMonths(d: Date, n: number): Date {
    return new Date(d.getFullYear(), d.getMonth() + n, 1);
}

function toIsoDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function toDisplayDate(d: Date, locale: string): string {
    return d.toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' });
}

export interface DateRangePickerOptions {
    container: HTMLElement;
    popup: HTMLElement;
    triggerCheckin: HTMLButtonElement;
    triggerCheckout: HTMLButtonElement;
    inputCheckin: HTMLInputElement;
    inputCheckout: HTMLInputElement;
    minNights: number;
    minBookingLeadDays: number;
    locale: string;
    hintCheckin: string;
    hintCheckout: string;
    labelClear: string;
    legendAvailable: string;
    legendSelected: string;
    legendBlocked: string;
    unavailableDates: Set<string>;
    onRangeSet: () => void;
}

type Phase = 'idle' | 'selecting-checkin' | 'selecting-checkout';

export function createDateRangePicker(opts: DateRangePickerOptions): void {
    const today = startOfDay(new Date());
    const earliestCheckin = addDays(today, opts.minBookingLeadDays);
    let phase: Phase = 'idle';
    let checkin: Date | null = null;
    let checkout: Date | null = null;
    let hoverDate: Date | null = null;
    let leftMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    const isMobile = (): boolean => window.innerWidth < 640;

    // ── Rendering ──────────────────────────────────────────────────────────

    function render(): void {
        opts.popup.innerHTML = '';

        // Step hint
        const hint = document.createElement('p');
        hint.className = 'dp__hint';
        hint.textContent = phase === 'selecting-checkout' ? opts.hintCheckout : opts.hintCheckin;
        opts.popup.appendChild(hint);

        // Navigation
        const nav = document.createElement('div');
        nav.className = 'dp__nav';
        nav.appendChild(mkNavBtn('‹', 'Previous month', () => { leftMonth = addMonths(leftMonth, -1); render(); }));
        nav.appendChild(mkNavBtn('›', 'Next month',     () => { leftMonth = addMonths(leftMonth,  1); render(); }));
        opts.popup.appendChild(nav);

        // Months
        const months = document.createElement('div');
        months.className = 'dp__months';
        const count = isMobile() ? 1 : 2;
        for (let i = 0; i < count; i++) {
            months.appendChild(renderMonth(addMonths(leftMonth, i)));
        }
        opts.popup.appendChild(months);

        const legend = document.createElement('div');
        legend.className = 'dp__legend';
        legend.appendChild(mkLegendItem('dp__legend-dot--available', opts.legendAvailable));
        legend.appendChild(mkLegendItem('dp__legend-dot--selected', opts.legendSelected));
        legend.appendChild(mkLegendItem('dp__legend-dot--blocked', opts.legendBlocked));
        opts.popup.appendChild(legend);

        // Clear button (only when a date is selected)
        if (checkin) {
            const footer = document.createElement('div');
            footer.className = 'dp__footer';
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'dp__clear';
            clearBtn.textContent = opts.labelClear;
            clearBtn.addEventListener('click', (e) => { e.stopPropagation(); resetSelection(); });
            footer.appendChild(clearBtn);
            opts.popup.appendChild(footer);
        }
    }

    function mkNavBtn(label: string, ariaLabel: string, onClick: () => void): HTMLButtonElement {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'dp__nav-btn';
        btn.textContent = label;
        btn.setAttribute('aria-label', ariaLabel);
        btn.addEventListener('click', (e) => { e.stopPropagation(); onClick(); });
        return btn;
    }

    function mkLegendItem(dotClass: string, label: string): HTMLElement {
        const item = document.createElement('span');
        item.className = 'dp__legend-item';

        const dot = document.createElement('span');
        dot.className = `dp__legend-dot ${dotClass}`;
        dot.setAttribute('aria-hidden', 'true');

        const text = document.createElement('span');
        text.textContent = label;

        item.appendChild(dot);
        item.appendChild(text);

        return item;
    }

    function renderMonth(monthStart: Date): HTMLElement {
        const wrap = document.createElement('div');
        wrap.className = 'dp__month';

        const title = document.createElement('div');
        title.className = 'dp__month-title';
        title.textContent = monthStart.toLocaleDateString(opts.locale, { month: 'long', year: 'numeric' });
        wrap.appendChild(title);

        // Weekday header — Monday first.
        // 2024-01-01 is a Monday, so offset i=0…6 gives Mon…Sun.
        const headRow = document.createElement('div');
        headRow.className = 'dp__grid dp__grid--head';
        for (let i = 0; i < 7; i++) {
            const probe = new Date(2024, 0, 1 + i);
            const name  = probe.toLocaleDateString(opts.locale, { weekday: 'short' });
            const cell  = document.createElement('div');
            cell.className = 'dp__head-cell';
            cell.textContent = name.length > 2 ? name.substring(0, 2) : name;
            headRow.appendChild(cell);
        }
        wrap.appendChild(headRow);

        // Day cells
        const grid = document.createElement('div');
        grid.className = 'dp__grid';

        // Empty leading cells (Monday = offset 0)
        const firstDow = (new Date(monthStart.getFullYear(), monthStart.getMonth(), 1).getDay() + 6) % 7;
        for (let i = 0; i < firstDow; i++) {
            const empty = document.createElement('div');
            empty.className = 'dp__day dp__day--empty';
            grid.appendChild(empty);
        }

        const daysInMonth = new Date(monthStart.getFullYear(), monthStart.getMonth() + 1, 0).getDate();
        for (let d = 1; d <= daysInMonth; d++) {
            grid.appendChild(renderDay(new Date(monthStart.getFullYear(), monthStart.getMonth(), d)));
        }

        wrap.appendChild(grid);
        return wrap;
    }

    function renderDay(date: Date): HTMLElement {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'dp__day';

        const span = document.createElement('span');
        span.className = 'dp__day-num';
        span.textContent = String(date.getDate());
        btn.appendChild(span);

        const isTod   = isSameDay(date, today);
        const isCi    = !!(checkin  && isSameDay(date, checkin));
        const isCo    = !!(checkout && isSameDay(date, checkout));

        // Range highlight: use confirmed checkout or hover preview
        const rangeEnd = checkout ?? (phase === 'selecting-checkout' ? hoverDate : null);
        const isRange  = !!(checkin && rangeEnd && date > checkin && date < rangeEnd);

        let disabled = date < earliestCheckin;
        const isUnavailable = opts.unavailableDates.has(toIsoDate(date));
        if (isUnavailable) {
            disabled = true;
            btn.classList.add('dp__day--blocked');
        }

        if (!disabled && phase === 'selecting-checkout' && checkin) {
            if (date <= checkin || date < addDays(checkin, opts.minNights)) disabled = true;
        }

        if (isTod)    btn.classList.add('dp__day--today');
        if (isCi)     btn.classList.add('dp__day--start');
        if (isCo)     btn.classList.add('dp__day--end');
        if (isRange)  btn.classList.add('dp__day--range');
        if (disabled) {
            btn.classList.add('dp__day--disabled');
            btn.disabled = true;
            return btn;
        }

        btn.addEventListener('mouseenter', () => {
            if (phase === 'selecting-checkout') {
                // Guard: only re-render when the hover date actually changes.
                // Without this, each render() destroys the button and the browser
                // fires another mouseenter on the new element → infinite render loop.
                if (!hoverDate || !isSameDay(hoverDate, date)) {
                    hoverDate = date;
                    render();
                }
            }
        });
        btn.addEventListener('click', (e) => { e.stopPropagation(); onDayClick(date); });
        return btn;
    }

    // ── State mutations ────────────────────────────────────────────────────

    function onDayClick(date: Date): void {
        if (phase === 'selecting-checkin') {
            checkin  = date;
            checkout = null;
            hoverDate = null;
            opts.inputCheckin.value  = toIsoDate(date);
            opts.inputCheckout.value = '';
            opts.inputCheckin.dispatchEvent(new Event('change', { bubbles: true }));
            phase = 'selecting-checkout';
            syncTriggers();
            render();
        } else if (phase === 'selecting-checkout' && checkin) {
            if (date >= addDays(checkin, opts.minNights)) {
                checkout  = date;
                hoverDate = null;
                opts.inputCheckout.value = toIsoDate(date);
                opts.inputCheckout.dispatchEvent(new Event('change', { bubbles: true }));
                phase = 'idle';
                syncTriggers();
                opts.onRangeSet();
                closePopup();
            }
        }
    }

    function resetSelection(): void {
        checkin  = null;
        checkout = null;
        hoverDate = null;
        opts.inputCheckin.value  = '';
        opts.inputCheckout.value = '';
        phase = 'selecting-checkin';
        syncTriggers();
        render();
    }

    function syncTriggers(): void {
        const ciVal = opts.triggerCheckin.querySelector<HTMLElement>('.dp-trigger__value');
        const coVal = opts.triggerCheckout.querySelector<HTMLElement>('.dp-trigger__value');

        if (ciVal) {
            ciVal.textContent = checkin  ? toDisplayDate(checkin,  opts.locale) : (ciVal.dataset['placeholder'] ?? '');
            ciVal.classList.toggle('dp-trigger__value--set', !!checkin);
        }
        if (coVal) {
            coVal.textContent = checkout ? toDisplayDate(checkout, opts.locale) : (coVal.dataset['placeholder'] ?? '');
            coVal.classList.toggle('dp-trigger__value--set', !!checkout);
        }

        opts.triggerCheckin.classList.toggle('dp-trigger--active',  phase === 'selecting-checkin');
        opts.triggerCheckout.classList.toggle('dp-trigger--active', phase === 'selecting-checkout');
    }

    // ── Popup open/close ───────────────────────────────────────────────────

    function openPopup(startPhase: 'selecting-checkin' | 'selecting-checkout'): void {
        phase = startPhase;
        opts.popup.hidden = false;
        render();
        syncTriggers();
    }

    function closePopup(): void {
        opts.popup.hidden = true;
        phase = 'idle';
        syncTriggers();
    }

    // ── Event wiring ───────────────────────────────────────────────────────

    opts.triggerCheckin.addEventListener('click', (e) => {
        e.stopPropagation();
        if (opts.popup.hidden) {
            openPopup('selecting-checkin');
        } else if (phase === 'selecting-checkin') {
            closePopup();
        } else {
            phase = 'selecting-checkin';
            render();
            syncTriggers();
        }
    });

    opts.triggerCheckout.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!checkin) {
            openPopup('selecting-checkin');
        } else if (opts.popup.hidden) {
            openPopup('selecting-checkout');
        } else if (phase === 'selecting-checkout') {
            closePopup();
        } else {
            phase = 'selecting-checkout';
            render();
            syncTriggers();
        }
    });

    // Clear hover preview when mouse leaves the calendar
    opts.popup.addEventListener('mouseleave', () => {
        if (phase === 'selecting-checkout' && hoverDate !== null) {
            hoverDate = null;
            if (!opts.popup.hidden) render();
        }
    });

    // Close on outside click
    document.addEventListener('click', (e: MouseEvent) => {
        if (!opts.popup.hidden && !opts.container.contains(e.target as Node)) {
            closePopup();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape' && !opts.popup.hidden) {
            e.preventDefault();
            closePopup();
        }
    });

    // Initial display
    syncTriggers();
}
