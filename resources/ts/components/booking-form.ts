/**
 * Booking form — AJAX submission with inline validation feedback.
 * No page reload on error or success; errors shown per field, success in-place.
 */
import { createDateRangePicker } from './date-picker';

interface ValidationErrors {
    [field: string]: string[];
}

interface ApiResponse {
    success?: boolean;
    errors?: ValidationErrors;
    message?: string;
}

interface QuoteResponse {
    available: boolean;
    nights?: number;
    total_cents?: number;
    parking_requested?: boolean;
    parking_cents?: number;
    guests?: number;
    message: string;
}

export function initBookingForm(): void {
    const form = document.querySelector<HTMLFormElement>('#booking-form');
    if (!form) return;

    const checkin   = form.querySelector<HTMLInputElement>('[name="checkin"]');
    const checkout  = form.querySelector<HTMLInputElement>('[name="checkout"]');
    const submitBtn = form.querySelector<HTMLButtonElement>('[type="submit"]');
    const termsCheckbox = form.querySelector<HTMLInputElement>('[name="accepted_terms"]');
    const successEl = document.querySelector<HTMLElement>('#booking-success');
    const priceBox = form.querySelector<HTMLElement>('[data-price-box]');
    const priceValue = form.querySelector<HTMLElement>('[data-price-value]');
    const priceDetail = form.querySelector<HTMLElement>('[data-price-detail]');
    const MIN_NIGHTS = parseInt(form.dataset['minNights'] ?? '3', 10);
    const MIN_BOOKING_LEAD_DAYS = parseInt(form.dataset['minBookingLeadDays'] ?? '0', 10);
    const MAX_BEDS = parseInt(form.dataset['maxBeds'] ?? '6', 10);
    const parkingCheckbox = form.querySelector<HTMLInputElement>('[name="parking_requested"]');
    const parkingPriceEl = form.querySelector<HTMLElement>('[data-parking-price]');
    const parkingFeeCents = parseInt(form.dataset['parkingFeeCents'] ?? '0', 10);
    const parkingPriceLabel = form.dataset['parkingPriceLabel'] ?? ':price / giorno';

    // --- Field error helpers ---

    // The dial-code prefix is a hidden input with no error span of its own —
    // show its validation errors on the visible "phone" field instead.
    const errorFieldFor = (fieldName: string): string => (fieldName === 'phone_prefix' ? 'phone' : fieldName);

    const setFieldError = (fieldName: string, message: string): void => {
        fieldName = errorFieldFor(fieldName);
        const span    = form.querySelector<HTMLElement>(`[data-error-for="${fieldName}"]`);
        const input   = form.querySelector<HTMLElement>(`[name="${fieldName}"]`);
        const trigger = document.getElementById(`dp-trigger-${fieldName}`);
        if (span)    { span.textContent = message; span.hidden = false; }
        if (input)   { input.classList.add('is-invalid'); }
        if (trigger) { trigger.classList.add('is-invalid'); }
    };

    const clearFieldError = (fieldName: string): void => {
        const span    = form.querySelector<HTMLElement>(`[data-error-for="${fieldName}"]`);
        const input   = form.querySelector<HTMLElement>(`[name="${fieldName}"]`);
        const trigger = document.getElementById(`dp-trigger-${fieldName}`);
        if (span)    { span.textContent = ''; span.hidden = true; }
        if (input)   { input.classList.remove('is-invalid'); }
        if (trigger) { trigger.classList.remove('is-invalid'); }
    };

    const clearAllErrors = (): void => {
        form.querySelectorAll<HTMLElement>('[data-error-for]').forEach(el => {
            el.textContent = '';
            el.hidden = true;
        });
        form.querySelectorAll<HTMLElement>('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
    };

    // --- Client-side date validation (instant feedback on change) ---

    const validateDates = (): boolean => {
        clearFieldError('checkin');
        clearFieldError('checkout');
        if (!checkin?.value || !checkout?.value) return true;

        const inDate  = new Date(checkin.value);
        const outDate = new Date(checkout.value);
        const today   = new Date();
        today.setHours(0, 0, 0, 0);

        const earliestCheckin = new Date(today);
        earliestCheckin.setDate(earliestCheckin.getDate() + MIN_BOOKING_LEAD_DAYS);
        if (inDate < earliestCheckin) {
            setFieldError('checkin', form.dataset['errorLeadTime'] ?? 'Check-in date is too soon.');
            return false;
        }
        if (outDate <= inDate) {
            setFieldError('checkout', form.dataset['errorOrder'] ?? 'Check-out must be after check-in.');
            return false;
        }
        const nights = Math.round((outDate.getTime() - inDate.getTime()) / 86_400_000);
        if (nights < MIN_NIGHTS) {
            setFieldError('checkout', form.dataset['errorMinNights'] ?? `Minimum stay is ${MIN_NIGHTS} nights.`);
            return false;
        }
        return true;
    };

    const validateGuestCount = (): boolean => {
        clearFieldError('adults');
        clearFieldError('children');

        const adults = parseInt(form.querySelector<HTMLSelectElement>('[name="adults"]')?.value ?? '0', 10) || 0;
        const children = parseInt(form.querySelector<HTMLSelectElement>('[name="children"]')?.value ?? '0', 10) || 0;

        if (adults + children > MAX_BEDS) {
            setFieldError('children', form.dataset['errorMaxGuests'] ?? `Maximum capacity is ${MAX_BEDS} guests.`);
            return false;
        }

        return true;
    };

    checkin?.addEventListener('change',  validateDates);
    checkout?.addEventListener('change', validateDates);

    // --- Legal consent checkbox: submit stays disabled until accepted ---

    const updateSubmitAvailability = (): void => {
        if (submitBtn && termsCheckbox) {
            submitBtn.disabled = !termsCheckbox.checked;
        }
    };

    termsCheckbox?.addEventListener('change', updateSubmitAvailability);
    updateSubmitAvailability();

    const hidePrice = (): void => {
        if (priceBox) {
            priceBox.hidden = true;
        }
    };

    const showPriceMessage = (value: string, detail: string): void => {
        if (!priceBox || !priceValue || !priceDetail) return;

        priceBox.hidden = false;
        priceValue.textContent = value;
        priceDetail.textContent = detail;
    };

    const formatCurrency = (totalCents: number): string => {
        const locale = form.dataset['locale'] ?? 'it-IT';

        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: 'EUR',
            maximumFractionDigits: 2,
        }).format(totalCents / 100);
    };

    const updateParkingLabel = (): void => {
        if (!parkingPriceEl || !parkingCheckbox) return;
        parkingPriceEl.hidden = !parkingCheckbox.checked;
        parkingPriceEl.textContent = parkingPriceLabel.replace(':price', formatCurrency(parkingFeeCents));
    };

    const fetchPriceQuote = (): void => {
        if (!checkin?.value || !checkout?.value || !validateDates()) {
            hidePrice();
            return;
        }

        const quoteUrl = form.dataset['quoteUrl'];
        if (!quoteUrl) {
            hidePrice();
            return;
        }

        const adultsEl   = form.querySelector<HTMLSelectElement>('[name="adults"]');
        const childrenEl = form.querySelector<HTMLSelectElement>('[name="children"]');
        const guests = (parseInt(adultsEl?.value ?? '1', 10) || 1)
                     + (parseInt(childrenEl?.value ?? '0', 10) || 0);

        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        const payload = new FormData();
        payload.append('checkin', checkin.value);
        payload.append('checkout', checkout.value);
        payload.append('guests', String(guests));
        payload.append('parking_requested', parkingCheckbox?.checked ? '1' : '0');

        fetch(quoteUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: payload,
        })
            .then(async (response) => {
                if (!response.ok) {
                    hidePrice();
                    return;
                }

                const data = await response.json() as QuoteResponse;
                if (!data.available || typeof data.total_cents !== 'number') {
                    hidePrice();
                    return;
                }

                // Only the total is shown to guests — the internal cost breakdown
                // (stay/cleaning/linen/avg per night) is not public information.
                let detail = data.message;
                if (data.parking_requested) {
                    detail += ` · ${parkingPriceLabel.replace(':price', formatCurrency(parkingFeeCents))}`;
                }
                showPriceMessage(formatCurrency(data.total_cents), detail);
            })
            .catch(() => {
                hidePrice();
            });
    };

    let quoteTimer: number | null = null;
    const scheduleQuote = (): void => {
        if (quoteTimer !== null) {
            window.clearTimeout(quoteTimer);
        }

        quoteTimer = window.setTimeout(fetchPriceQuote, 200);
    };

    checkin?.addEventListener('change', scheduleQuote);
    checkout?.addEventListener('change', scheduleQuote);

    // Re-fetch quote when guest count changes (affects linen fee)
    form.querySelector<HTMLSelectElement>('[name="adults"]')?.addEventListener('change', scheduleQuote);
    form.querySelector<HTMLSelectElement>('[name="children"]')?.addEventListener('change', scheduleQuote);
    parkingCheckbox?.addEventListener('change', () => {
        updateParkingLabel();
        scheduleQuote();
    });
    form.querySelector<HTMLSelectElement>('[name="adults"]')?.addEventListener('change', validateGuestCount);
    form.querySelector<HTMLSelectElement>('[name="children"]')?.addEventListener('change', validateGuestCount);
    updateParkingLabel();

    // --- Date range picker integration ---

    const dpContainer = document.getElementById('date-range-picker') as HTMLElement | null;
    const dpPopup     = document.getElementById('dp-popup') as HTMLElement | null;
    const dpTriggerCi = document.getElementById('dp-trigger-checkin') as HTMLButtonElement | null;
    const dpTriggerCo = document.getElementById('dp-trigger-checkout') as HTMLButtonElement | null;
    const unavailableDatesRaw = form.dataset['unavailableDates'] ?? '[]';
    let unavailableDates = new Set<string>();

    try {
        const parsed = JSON.parse(unavailableDatesRaw);
        if (Array.isArray(parsed)) {
            unavailableDates = new Set(parsed.filter((date): date is string => typeof date === 'string'));
        }
    } catch {
        unavailableDates = new Set<string>();
    }

    if (dpContainer && dpPopup && dpTriggerCi && dpTriggerCo && checkin && checkout) {
        createDateRangePicker({
            container:       dpContainer,
            popup:           dpPopup,
            triggerCheckin:  dpTriggerCi,
            triggerCheckout: dpTriggerCo,
            inputCheckin:    checkin,
            inputCheckout:   checkout,
            minNights:       MIN_NIGHTS,
            minBookingLeadDays: MIN_BOOKING_LEAD_DAYS,
            locale:          dpContainer.dataset['locale'] ?? 'it',
            hintCheckin:     dpContainer.dataset['hintCheckin']  ?? '',
            hintCheckout:    dpContainer.dataset['hintCheckout'] ?? '',
            labelClear:      dpContainer.dataset['labelClear']   ?? 'Clear',
            legendAvailable: dpContainer.dataset['legendAvailable'] ?? 'Available',
            legendSelected:  dpContainer.dataset['legendSelected'] ?? 'Selected',
            legendBlocked:   dpContainer.dataset['legendBlocked'] ?? 'Occupied',
            unavailableDates,
            onRangeSet: () => {
                clearFieldError('checkin');
                clearFieldError('checkout');
                scheduleQuote();
            },
        });
    }

    // --- Loading state ---

    const setLoading = (loading: boolean): void => {
        if (!submitBtn) return;
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.dataset['originalText'] = submitBtn.textContent ?? '';
            submitBtn.textContent = form.dataset['labelLoading'] ?? '…';
        } else {
            submitBtn.textContent = submitBtn.dataset['originalText'] ?? submitBtn.textContent;
            updateSubmitAvailability();
        }
    };

    // --- Show success in-place ---

    const showSuccess = (): void => {
        form.hidden = true;
        if (successEl) {
            successEl.hidden = false;
            successEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    // --- AJAX submission ---

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        clearAllErrors();
        if (!validateDates()) return;
        if (!validateGuestCount()) return;

        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

        setLoading(true);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: new FormData(form),
        })
            .then(async (response) => {
                if (response.ok) {
                    showSuccess();
                    return;
                }
                if (response.status === 422) {
                    const data: ApiResponse = await response.json();
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([field, messages]) => {
                            setFieldError(field, messages[0] ?? '');
                        });
                        // Scroll to first invalid field
                        const firstInvalid = form.querySelector<HTMLElement>('.is-invalid');
                        firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
                setFieldError('_form', form.dataset['errorServer'] ?? 'An error occurred. Please try again.');
            })
            .catch(() => {
                setFieldError('_form', form.dataset['errorServer'] ?? 'Network error. Please try again.');
            })
            .finally(() => {
                setLoading(false);
            });
    });
}
