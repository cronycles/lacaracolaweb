/**
 * Guest picker — single collapsed field ("🧑 2 · 🧒 1 …") that opens a popup
 * with +/- steppers for adults/children/babies/pets. Mirrors the open/close
 * behaviour of the date range picker (dp-trigger/dp-popup).
 */

const ROW_ICONS: Record<string, string> = {
    adults: '🧑',
    children: '🧒',
    babies: '👶',
    pets: '🐾',
};

export function initGuestPicker(): void {
    const container = document.getElementById('guest-picker');
    const trigger = document.getElementById('guest-trigger') as HTMLButtonElement | null;
    const popup = document.getElementById('guest-popup');
    if (!container || !trigger || !popup) return;

    const summaryEl = trigger.querySelector<HTMLElement>('[data-guest-summary]');
    const doneBtn = popup.querySelector<HTMLButtonElement>('[data-guest-done]');
    const rows = Array.from(popup.querySelectorAll<HTMLElement>('[data-guest-row]'));

    const getInput = (name: string): HTMLInputElement | null =>
        container.querySelector<HTMLInputElement>(`input[name="${name}"]`);

    const updateSummary = (): void => {
        if (!summaryEl) return;
        const parts: string[] = [];
        rows.forEach((row) => {
            const name = row.dataset['guestRow'];
            if (!name) return;
            const value = parseInt(getInput(name)?.value ?? '0', 10) || 0;
            if (name === 'adults' || value > 0) {
                parts.push(`${ROW_ICONS[name] ?? ''} ${value}`);
            }
        });
        summaryEl.textContent = parts.join(' · ');
        summaryEl.classList.add('dp-trigger__value--set');
    };

    const updateRowUi = (row: HTMLElement): void => {
        const name = row.dataset['guestRow'];
        if (!name) return;
        const min = parseInt(row.dataset['min'] ?? '0', 10);
        const max = parseInt(row.dataset['max'] ?? '99', 10);
        const value = parseInt(getInput(name)?.value ?? String(min), 10) || min;

        const countEl = row.querySelector<HTMLElement>('[data-guest-count]');
        const decBtn = row.querySelector<HTMLButtonElement>('[data-action="decrement"]');
        const incBtn = row.querySelector<HTMLButtonElement>('[data-action="increment"]');
        if (countEl) countEl.textContent = String(value);
        if (decBtn) decBtn.disabled = value <= min;
        if (incBtn) incBtn.disabled = value >= max;
    };

    const setValue = (row: HTMLElement, newValue: number): void => {
        const name = row.dataset['guestRow'];
        const input = name ? getInput(name) : null;
        if (!input) return;

        const min = parseInt(row.dataset['min'] ?? '0', 10);
        const max = parseInt(row.dataset['max'] ?? '99', 10);
        const clamped = Math.min(max, Math.max(min, newValue));
        if (String(clamped) === input.value) return;

        input.value = String(clamped);
        input.dispatchEvent(new Event('change', { bubbles: true }));
        updateRowUi(row);
        updateSummary();
    };

    rows.forEach((row) => {
        updateRowUi(row);
        const name = row.dataset['guestRow'] ?? '';

        row.querySelector('[data-action="decrement"]')?.addEventListener('click', () => {
            const current = parseInt(getInput(name)?.value ?? '0', 10) || 0;
            setValue(row, current - 1);
        });
        row.querySelector('[data-action="increment"]')?.addEventListener('click', () => {
            const current = parseInt(getInput(name)?.value ?? '0', 10) || 0;
            setValue(row, current + 1);
        });
    });

    updateSummary();

    // --- Popup open/close (same pattern as the date range picker) ---

    const openPopup = (): void => {
        popup.hidden = false;
        trigger.classList.add('dp-trigger--active');
    };

    const closePopup = (): void => {
        popup.hidden = true;
        trigger.classList.remove('dp-trigger--active');
    };

    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        if (popup.hidden) openPopup(); else closePopup();
    });

    doneBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        closePopup();
    });

    document.addEventListener('click', (e: MouseEvent) => {
        if (!popup.hidden && !container.contains(e.target as Node)) {
            closePopup();
        }
    });

    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape' && !popup.hidden) {
            e.preventDefault();
            closePopup();
        }
    });
}
