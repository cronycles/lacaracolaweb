/**
 * Booking form — date validation and availability check feedback.
 * The actual availability data is loaded from Laravel (server-side rendered into a JS variable).
 */

export function initBookingForm(): void {
    const form = document.querySelector<HTMLFormElement>('#booking-form');
    if (!form) return;

    const checkin = form.querySelector<HTMLInputElement>('[name="checkin"]');
    const checkout = form.querySelector<HTMLInputElement>('[name="checkout"]');
    const errorEl = form.querySelector<HTMLElement>('.booking-form__error');
    const MIN_NIGHTS = 3;

    const showError = (msg: string): void => {
        if (!errorEl) return;
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
    };

    const clearError = (): void => {
        if (!errorEl) return;
        errorEl.textContent = '';
        errorEl.style.display = 'none';
    };

    const validate = (): boolean => {
        clearError();
        if (!checkin?.value || !checkout?.value) return true;

        const inDate = new Date(checkin.value);
        const outDate = new Date(checkout.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (inDate < today) {
            showError(form.dataset['errorPast'] ?? 'Check-in date cannot be in the past.');
            return false;
        }

        if (outDate <= inDate) {
            showError(form.dataset['errorOrder'] ?? 'Check-out must be after check-in.');
            return false;
        }

        const nights = Math.round((outDate.getTime() - inDate.getTime()) / 86_400_000);
        if (nights < MIN_NIGHTS) {
            showError((form.dataset['errorMinNights'] ?? `Minimum stay is ${MIN_NIGHTS} nights.`).replace('{n}', String(MIN_NIGHTS)));
            return false;
        }

        return true;
    };

    checkin?.addEventListener('change', validate);
    checkout?.addEventListener('change', validate);

    form.addEventListener('submit', (e) => {
        if (!validate()) e.preventDefault();
    });
}
