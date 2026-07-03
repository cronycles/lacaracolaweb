/**
 * La Caracola — Admin panel TypeScript entry point.
 * Initialises admin-specific UI modules.
 */

import flatpickr from 'flatpickr';
import { Italian } from 'flatpickr/dist/l10n/it';
import 'flatpickr/dist/flatpickr.min.css';
import { initPeopleReportingFields, initDocumentIssueFields, initCountryComboFields, initDocumentTypeToggle } from './people-reporting-fields';
import { initPhonePrefixSelects } from './components/phone-prefix-select';

document.addEventListener('DOMContentLoaded', () => {
    // Initialise all native date inputs with Italian locale and dd/mm/yyyy display format.
    // altInput: true shows a visual text input in altFormat (dd/mm/yyyy),
    // while the underlying input stays in dateFormat (yyyy-mm-dd) for server submission.
    flatpickr('input[type="date"]', {
        locale: Italian,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: true,
    });

    initCountryComboFields();
    initPeopleReportingFields();
    initDocumentIssueFields();
    initDocumentTypeToggle();
    initPhonePrefixSelects();
});
