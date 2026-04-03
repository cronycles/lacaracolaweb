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
    stay_cents?: number;
    cleaning_cents?: number;
    total_cents?: number;
    nights?: number;
    message: string;
}

export function initBookingForm(): void {
    const form = document.querySelector<HTMLFormElement>('#booking-form');
    if (!form) return;

    const checkin   = form.querySelector<HTMLInputElement>('[name="checkin"]');
    const checkout  = form.querySelector<HTMLInputElement>('[name="checkout"]');
    const submitBtn = form.querySelector<HTMLButtonElement>('[type="submit"]');
    const successEl = document.querySelector<HTMLElement>('#booking-success');
    const priceBox = form.querySelector<HTMLElement>('[data-price-box]');
    const priceValue = form.querySelector<HTMLElement>('[data-price-value]');
    const priceBreakdown = form.querySelector<HTMLElement>('[data-price-breakdown]');
    const priceDetail = form.querySelector<HTMLElement>('[data-price-detail]');
    const MIN_NIGHTS = parseInt(form.dataset['minNights'] ?? '3', 10);

    // --- Field error helpers ---

    const setFieldError = (fieldName: string, message: string): void => {
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

        if (inDate < today) {
            setFieldError('checkin', form.dataset['errorPast'] ?? 'Check-in cannot be in the past.');
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

    checkin?.addEventListener('change',  validateDates);
    checkout?.addEventListener('change', validateDates);

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

        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        const payload = new FormData();
        payload.append('checkin', checkin.value);
        payload.append('checkout', checkout.value);

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

                if (priceBreakdown) {
                    const stayLabel = form.dataset['priceStayLabel'] ?? 'Soggiorno';
                    const cleaningLabel = form.dataset['priceCleaningLabel'] ?? 'Pulizie';
                    const stayCents = data.stay_cents ?? 0;
                    const cleaningCents = data.cleaning_cents ?? 0;
                    priceBreakdown.textContent = `${stayLabel}: ${formatCurrency(stayCents)} · ${cleaningLabel}: ${formatCurrency(cleaningCents)}`;
                }

                showPriceMessage(formatCurrency(data.total_cents), data.message);
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

    // --- Date range picker integration ---

    const dpContainer = document.getElementById('date-range-picker') as HTMLElement | null;
    const dpPopup     = document.getElementById('dp-popup') as HTMLElement | null;
    const dpTriggerCi = document.getElementById('dp-trigger-checkin') as HTMLButtonElement | null;
    const dpTriggerCo = document.getElementById('dp-trigger-checkout') as HTMLButtonElement | null;

    if (dpContainer && dpPopup && dpTriggerCi && dpTriggerCo && checkin && checkout) {
        createDateRangePicker({
            container:       dpContainer,
            popup:           dpPopup,
            triggerCheckin:  dpTriggerCi,
            triggerCheckout: dpTriggerCo,
            inputCheckin:    checkin,
            inputCheckout:   checkout,
            minNights:       MIN_NIGHTS,
            locale:          dpContainer.dataset['locale'] ?? 'it',
            hintCheckin:     dpContainer.dataset['hintCheckin']  ?? '',
            hintCheckout:    dpContainer.dataset['hintCheckout'] ?? '',
            labelClear:      dpContainer.dataset['labelClear']   ?? 'Clear',
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
        submitBtn.disabled = loading;
        if (loading) {
            submitBtn.dataset['originalText'] = submitBtn.textContent ?? '';
            submitBtn.textContent = form.dataset['labelLoading'] ?? '…';
        } else {
            submitBtn.textContent = submitBtn.dataset['originalText'] ?? submitBtn.textContent;
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
