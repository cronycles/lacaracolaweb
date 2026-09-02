import { createDateRangePicker } from './date-picker';

interface PortalSuggestion {
    total_cents: number;
    avg_per_night_cents: number;
    commission_rate: number;
}

interface SimulationResponse {
    available: boolean;
    nights?: number;
    guests?: number;
    stay_cents?: number;
    stay_gross_cents?: number;
    stay_discount_cents?: number;
    length_discount_rate?: number;
    tax_gross_up_cents?: number;
    cleaning_cents?: number;
    linen_cents?: number;
    total_cents?: number;
    parking_cents?: number;
    avg_per_night_cents?: number;
    portals?: Record<string, PortalSuggestion>;
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
    const discountEl = document.querySelector<HTMLElement>('#pricing-sim-discount');
    const taxEl = document.querySelector<HTMLElement>('#pricing-sim-tax');
    const portalsBox = document.querySelector<HTMLElement>('#pricing-sim-portals');
    const errorEl = document.querySelector<HTMLElement>('#pricing-sim-error');

    if (!checkinInput || !checkoutInput || !resultBox || !summaryEl || !breakdownEl || !discountEl || !taxEl || !portalsBox || !errorEl) {
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

        const guestsInput = form.querySelector<HTMLInputElement>('#sim-guests');
        const guests = Math.max(1, parseInt(guestsInput?.value ?? '2', 10) || 2);

        const payload = new FormData();
        payload.append('checkin', checkinInput.value);
        payload.append('checkout', checkoutInput.value);
        payload.append('guests', String(guests));
        const parkingInput = form.querySelector<HTMLInputElement>('#sim-parking');
        payload.append('parking_requested', parkingInput?.checked ? '1' : '0');

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

                const nights       = data.nights ?? 0;
                const stay         = data.stay_cents ?? 0;
                const cleaning     = data.cleaning_cents ?? 0;
                const linen        = data.linen_cents ?? 0;
                const parking      = data.parking_cents ?? 0;
                const avgPerNight  = data.avg_per_night_cents ?? 0;
                const total        = data.total_cents ?? 0;
                const stayDiscount = data.stay_discount_cents ?? 0;
                const lengthDiscountRate = data.length_discount_rate ?? 0;
                const taxGrossUp   = data.tax_gross_up_cents ?? 0;

                summaryEl.textContent = `${nights} notti · ${guests} ospiti · Totale casa ${formatCurrency(total)} · Media ${formatCurrency(avgPerNight)}/notte`;
                const parkingDetail = parking > 0 ? ` · Parcheggio ${formatCurrency(parking)} da pagare in loco` : '';
                breakdownEl.textContent = `Soggiorno ${formatCurrency(stay)} · Pulizie ${formatCurrency(cleaning)} · Biancheria ${formatCurrency(linen)}${parkingDetail}`;

                discountEl.textContent = stayDiscount > 0
                    ? `Sconto soggiorno (${(lengthDiscountRate * 100).toFixed(0)}%): −${formatCurrency(stayDiscount)}`
                    : '';

                taxEl.textContent = taxGrossUp > 0
                    ? `Maggiorazione fiscale su costi accessori: +${formatCurrency(taxGrossUp)}`
                    : '';

                const portals = data.portals;
                if (portals) {
                    const portalLabels: Record<string, string> = { airbnb: 'airbnb', booking: 'booking', hometogo: 'hometogo' };
                    (Object.keys(portalLabels) as Array<keyof typeof portalLabels>).forEach((key) => {
                        const portalEl = document.querySelector<HTMLElement>(`#pricing-sim-portal-${key}`);
                        const portal = portals[key];
                        if (portalEl && portal) {
                            portalEl.textContent = `${formatCurrency(portal.total_cents)} · ${formatCurrency(portal.avg_per_night_cents)}/notte (comm. ${(portal.commission_rate * 100).toFixed(1)}%)`;
                        }
                    });
                    portalsBox.style.display = 'grid';
                } else {
                    portalsBox.style.display = 'none';
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

    const guestsInputEl = form.querySelector<HTMLInputElement>('#sim-guests');
    guestsInputEl?.addEventListener('change', runSimulation);
    form.querySelector<HTMLInputElement>('#sim-parking')?.addEventListener('change', runSimulation);

    if (container && popup && triggerCheckin && triggerCheckout) {
        createDateRangePicker({
            container,
            popup,
            triggerCheckin,
            triggerCheckout,
            inputCheckin: checkinInput,
            inputCheckout: checkoutInput,
            minNights: parseInt(form.dataset['minNights'] ?? '3', 10),
            minBookingLeadDays: 0,
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
