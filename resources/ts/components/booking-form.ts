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

export function initBookingForm(): void {
    const form = document.querySelector<HTMLFormElement>('#booking-form');
    if (!form) return;

    const checkin   = form.querySelector<HTMLInputElement>('[name="checkin"]');
    const checkout  = form.querySelector<HTMLInputElement>('[name="checkout"]');
    const submitBtn = form.querySelector<HTMLButtonElement>('[type="submit"]');
    const successEl = document.querySelector<HTMLElement>('#booking-success');
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
