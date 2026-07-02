/**
 * People reporting fields — conditional UI for guest reporting data.
 *
 * Works for both the single-person edit form (one instance per page) and the
 * multi-guest reporting form (N instances per page).  All lookups are scoped to
 * the nearest `.a-card` ancestor so each guest row is independent.
 *
 * Birth country logic:
 *  - IT  → show birth_province field; enable ComboSelect for municipality (locked to comuni list)
 *  - other → hide birth_province; plain text municipality
 *
 * Document issue country logic:
 *  - IT  → show document_issue_place; enable ComboSelect (locked to comuni list)
 *  - other → hide document_issue_place (country code alone is sufficient)
 *
 * Country selects (Paese di residenza, Nazionalità, Paese di nascita, Paese di rilascio)
 * are rendered as <input type="text" data-country-combo> and converted to ComboSelects
 * by initCountryComboFields().  After that, the committed country code is available on
 * the element via dataset.comboValue (kept in sync by ComboSelect._commit).
 *
 * window.COMUNI_VALIDI must be set by the Blade view (via @json) before this runs.
 * window.COUNTRIES_MAP must be set by the Blade view (via @json) before this runs.
 * If absent, falls back to a short capoluogo/country list.
 */

import { ComboSelect } from './components/combo-select';

declare global {
    interface Window {
        COMUNI_VALIDI?: string[];
        COUNTRIES_MAP?: Record<string, string>;
    }
}

// Fallback short list (capoluogo only) — used when window.COMUNI_VALIDI is not available.
const ITALIAN_COMUNI_FALLBACK: string[] = [
    'Agrigento','Alessandria','Ancona','Andora','Aosta','Arezzo','Ascoli Piceno',
    'Asti','Avellino','Bari','Barletta','Belluno','Benevento','Bergamo','Biella',
    'Bologna','Bolzano','Brescia','Brindisi','Cagliari','Caltanissetta','Campobasso',
    'Caserta','Catania','Catanzaro','Chieti','Como','Cosenza','Cremona','Crotone',
    'Cuneo','Enna','Fermo','Ferrara','Firenze','Foggia','Forlì','Frosinone',
    'Genova','Gorizia','Grosseto','Imperia','Isernia',"L'Aquila",'La Spezia',
    'Latina','Lecce','Lecco','Livorno','Lodi','Lucca','Macerata','Mantova',
    'Massa','Matera','Messina','Milano','Modena','Monza','Napoli','Novara',
    'Nuoro','Oristano','Padova','Palermo','Parma','Pavia','Perugia','Pesaro',
    'Pescara','Piacenza','Pisa','Pistoia','Pordenone','Potenza','Prato',
    'Ragusa','Ravenna','Reggio Calabria','Reggio Emilia','Rieti','Rimini',
    'Roma','Rovigo','Salerno','Sassari','Savona','Siena','Siracusa','Sondrio',
    'Taranto','Teramo','Terni','Torino','Trapani','Trento','Treviso','Trieste',
    'Udine','Varese','Venezia','Verbania','Vercelli','Verona','Vibo Valentia',
    'Vicenza','Viterbo',
];

function getComuniList(): string[] {
    return window.COMUNI_VALIDI ?? ITALIAN_COMUNI_FALLBACK;
}

/** Convert the COUNTRIES_MAP (code → name) to ComboOption pairs sorted by label. */
function getCountriesList(): { label: string; value: string }[] {
    const map = window.COUNTRIES_MAP ?? {};
    return Object.entries(map).map(([value, label]) => ({ label, value }));
}

/**
 * Initialise searchable ComboSelect for every <input data-country-combo> element.
 * Must run before initPeopleReportingFields / initDocumentIssueFields so that
 * dataset.comboValue is already populated when those functions read the country code.
 */
function initCountryComboFields(): void {
    document.querySelectorAll<HTMLInputElement>('[data-country-combo]').forEach((input) => {
        const cs = new ComboSelect(input);
        const initialValue = input.dataset.currentValue ?? '';
        cs.enable(getCountriesList(), initialValue);
    });
}

export { initCountryComboFields };

/**
 * Initialise birth-country → municipality/province logic for every guest row.
 * Scoped to the nearest `.a-card` so multi-guest forms work correctly.
 */
function initPeopleReportingFields(): void {
    document.querySelectorAll<HTMLInputElement>('[data-reporting-birth-country]').forEach((countryEl) => {
        const card = countryEl.closest<HTMLElement>('.a-card') ?? document.documentElement;
        const municipalityInput = card.querySelector<HTMLInputElement>('[data-reporting-birth-municipality]');
        const provinceGroup     = card.querySelector<HTMLElement>('[data-birth-province-group]');

        if (!municipalityInput) return;
        const mi = municipalityInput; // stable non-null ref for closures

        const comboSelect = new ComboSelect(mi);
        const initialValue = mi.getAttribute('data-current-value') ?? mi.value;

        /** Read the committed country code, supporting both <select> and ComboSelect input. */
        const getCountryCode = (): string =>
            countryEl.dataset.comboValue ?? countryEl.value;

        function update(countryCode: string): void {
            const isItaly = countryCode === 'IT';

            if (provinceGroup) provinceGroup.style.display = isItaly ? '' : 'none';

            // Update the associated <label> text (label is now on the wrapper)
            const label = mi.closest('.form-group')?.querySelector('label');
            if (label) {
                const hasAsterisk = label.textContent?.trimEnd().endsWith('*') ?? false;
                label.textContent = (isItaly ? 'Comune di nascita' : 'Città di nascita') + (hasAsterisk ? ' *' : '');
            }

            mi.placeholder = isItaly ? 'Cerca comune...' : 'Es: Monaco';

            if (isItaly) {
                comboSelect.enable(getComuniList(), comboSelect.getValue() || initialValue);
            } else {
                comboSelect.disable();
            }
        }

        update(getCountryCode());

        countryEl.addEventListener('change', () => {
            update(getCountryCode());
            if (getCountryCode() !== 'IT') {
                mi.value = '';
            }
        });
    });
}

export { initPeopleReportingFields };

/**
 * Initialise document-issue-country → issue-place logic for every guest row.
 * Scoped to the nearest `.a-card` so multi-guest forms work correctly.
 */
function initDocumentIssueFields(): void {
    document.querySelectorAll<HTMLInputElement>('[data-reporting-issue-country]').forEach((issueCountryEl) => {
        const card            = issueCountryEl.closest<HTMLElement>('.a-card') ?? document.documentElement;
        const issuePlaceGroup = card.querySelector<HTMLElement>('[data-document-issue-place-group]');
        const issuePlaceInput = card.querySelector<HTMLInputElement>('[data-reporting-issue-municipality]');

        if (!issuePlaceGroup) return;

        const comboSelect    = issuePlaceInput ? new ComboSelect(issuePlaceInput) : null;
        const initialValue   = issuePlaceInput?.getAttribute('data-current-value') ?? issuePlaceInput?.value ?? '';

        /** Read the committed country code, supporting both <select> and ComboSelect input. */
        const getCountryCode = (): string =>
            issueCountryEl.dataset.comboValue ?? issueCountryEl.value;

        function update(countryCode: string): void {
            const isItaly = countryCode === 'IT';
            issuePlaceGroup!.style.display = isItaly ? '' : 'none';
            if (comboSelect && issuePlaceInput) {
                if (isItaly) {
                    issuePlaceInput.placeholder = 'Cerca comune...';
                    comboSelect.enable(getComuniList(), comboSelect.getValue() || initialValue);
                } else {
                    comboSelect.disable();
                    issuePlaceInput.value = '';
                }
            }
        }

        update(getCountryCode());

        issueCountryEl.addEventListener('change', () => update(getCountryCode()));
    });
}

export { initDocumentIssueFields };

