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
});
