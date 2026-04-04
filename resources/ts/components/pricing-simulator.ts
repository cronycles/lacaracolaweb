import { createDateRangePicker } from './date-picker';

interface SimulationResponse {
    available: boolean;
    nights?: number;
    stay_cents?: number;
    discount_percent?: number;
    discount_cents?: number;
    discounted_stay_cents?: number;
    cleaning_cents?: number;
    total_cents?: number;
    message?: string;
}

export function initPricingSimulator(): void {
    const form = document.querySelector<HTMLFormElement>('#pricing-sim-form');
    if (!form) return;

    const checkinInput = form.querySelector<HTMLInputElement>('#sim-checkin');
    const checkoutInput = form.querySelector<HTMLInputElement>('#sim-checkout');
    const resultBox = document.querySelector<HTMLElement>('#pricing-sim-result');
    const summaryEl = document.querySelector<HTMLElement>('#pricing-sim-summary');
    const breakdownEl = document.querySelector<HTMLElement>('#pricing-sim-breakdown');
    const errorEl = document.querySelector<HTMLElement>('#pricing-sim-error');

    if (!checkinInput || !checkoutInput || !resultBox || !summaryEl || !breakdownEl || !errorEl) {
        return;
    }

    const locale = form.dataset['locale'] ?? 'it-IT';
    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const simulateUrl = form.dataset['simulateUrl'] ?? '';

    const formatCurrency = (cents: number): string => {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: 'EUR',
            maximumFractionDigits: 2,
        }).format(cents / 100);
    };

    const showError = (message: string): void => {
        errorEl.textContent = message;
        errorEl.style.display = 'block';
        resultBox.style.display = 'none';
    };

    const clearMessages = (): void => {
        errorEl.style.display = 'none';
        errorEl.textContent = '';
    };

    const runSimulation = (): void => {
        if (!simulateUrl || !checkinInput.value || !checkoutInput.value) {
            resultBox.style.display = 'none';
            return;
        }

        clearMessages();

        const payload = new FormData();
        payload.append('checkin', checkinInput.value);
        payload.append('checkout', checkoutInput.value);

        fetch(simulateUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: payload,
        })
            .then(async (response) => {
                const data = await response.json() as SimulationResponse;

                if (!response.ok || !data.available) {
                    showError(data.message ?? 'Simulazione non disponibile per il periodo selezionato.');
                    return;
                }

                const nights = data.nights ?? 0;
                const stay = data.stay_cents ?? 0;
                const discountPercent = data.discount_percent ?? 0;
                const discount = data.discount_cents ?? 0;
                const discountedStay = data.discounted_stay_cents ?? stay;
                const cleaning = data.cleaning_cents ?? 0;
                const total = data.total_cents ?? 0;

                summaryEl.textContent = `${nights} notti · Totale ${formatCurrency(total)}`;
                if (discount > 0 && discountPercent > 0) {
                    breakdownEl.textContent = `Soggiorno base ${formatCurrency(stay)} · Sconto ${discountPercent}% (-${formatCurrency(discount)}) · Soggiorno scontato ${formatCurrency(discountedStay)} · Pulizie ${formatCurrency(cleaning)}`;
                } else {
                    breakdownEl.textContent = `Soggiorno ${formatCurrency(stay)} · Pulizie ${formatCurrency(cleaning)}`;
                }

                resultBox.style.display = 'block';
            })
            .catch(() => {
                showError('Errore di rete durante la simulazione prezzo.');
            });
    };

    const container = document.getElementById('pricing-sim-date-picker');
    const popup = document.getElementById('pricing-sim-dp-popup');
    const triggerCheckin = document.getElementById('pricing-sim-trigger-checkin') as HTMLButtonElement | null;
    const triggerCheckout = document.getElementById('pricing-sim-trigger-checkout') as HTMLButtonElement | null;

    if (container && popup && triggerCheckin && triggerCheckout) {
        createDateRangePicker({
            container,
            popup,
            triggerCheckin,
            triggerCheckout,
            inputCheckin: checkinInput,
            inputCheckout: checkoutInput,
            minNights: parseInt(form.dataset['minNights'] ?? '3', 10),
            locale,
            hintCheckin: 'Seleziona la data di arrivo',
            hintCheckout: `Seleziona la data di partenza (min. ${form.dataset['minNights'] ?? '3'} notti)`,
            labelClear: 'Cancella date',
            legendAvailable: 'Disponibile',
            legendSelected: 'Selezionato',
            legendBlocked: 'Occupato',
            unavailableDates: new Set<string>(),
            onRangeSet: runSimulation,
        });
    }
}
