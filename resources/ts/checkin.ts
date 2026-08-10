/**
 * Public online check-in page — bootstraps the shared guest-reporting field
 * logic (country/municipality combos, document-issue toggling) using the
 * translated labels set on `window.CHECKIN_I18N` by the Blade view.
 *
 * Unlike the admin form, the "tipo alloggiato" classification is fully
 * automatic and not user-editable here, so `initDocumentTypeToggle()` is not
 * needed: the document fields' visibility is decided server-side per guest.
 */
import { initCountryComboFields, initPeopleReportingFields, initDocumentIssueFields } from './people-reporting-fields';

document.addEventListener('DOMContentLoaded', () => {
    initCountryComboFields();
    initPeopleReportingFields();
    initDocumentIssueFields();

    const form = document.querySelector<HTMLFormElement>('#checkin-form');
    const progress = document.querySelector<HTMLElement>('[data-checkin-progress]');
    const progressCount = document.querySelector<HTMLElement>('[data-checkin-progress-count]');
    const submitError = document.querySelector<HTMLElement>('[data-checkin-submit-error]');

    if (!form || !progress || !progressCount || !submitError) {
        return;
    }

    const requiredGuests = Number(progress.dataset.requiredGuests ?? 0);
    const presentGuests = Number(progress.dataset.presentGuests ?? 0);
    const progressTemplate = progress.dataset.progressTemplate ?? '__COMPLETED__ / ' + requiredGuests;
    const guestForms = Array.from(form.querySelectorAll<HTMLInputElement>('input[name$="[person_id]"]'));

    const updateProgress = (): void => {
        const completedGuests = guestForms.filter((guestInput) => {
            const guestIndex = guestInput.name.match(/guests\[(\d+)\]/u)?.[1];
            return guestIndex !== undefined
                && form.querySelectorAll(`[name^="guests[${guestIndex}]"][required]`).length > 0
                && Array.from(form.querySelectorAll<HTMLInputElement | HTMLSelectElement>(`[name^="guests[${guestIndex}]"]`))
                    .every((field) => field.checkValidity());
        }).length;

        progressCount.textContent = progressTemplate.replace('__COMPLETED__', String(completedGuests));
        progress.dataset.presentGuests = String(presentGuests);
    };

    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
    form.addEventListener('submit', (event) => {
        submitError.hidden = true;

        if (presentGuests < requiredGuests || !form.checkValidity()) {
            event.preventDefault();
            submitError.hidden = false;
            form.reportValidity();
            submitError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    updateProgress();
});
