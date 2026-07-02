/**
 * People reporting fields — conditional UI for guest reporting data.
 *
 * Works for both the single-person edit form (one instance per page) and the
 * multi-guest reporting form (N instances per page).  All lookups are scoped to
 * the nearest `.a-card` ancestor so each guest row is independent.
 *
 * Birth country logic:
 *  - IT  → show birth_province field; attach datalist for municipality; enforce valid comune
 *  - other → hide birth_province; plain text municipality; clear validity
 *
 * Document issue country logic:
 *  - IT  → show document_issue_place; enforce valid comune
 *  - other → hide document_issue_place (country code alone is sufficient)
 *
 * window.COMUNI_VALIDI must be set by the Blade view (via @json) before this runs.
 * If absent, falls back to a short capoluogo list for datalist suggestions only.
 */

declare global {
    interface Window {
        COMUNI_VALIDI?: string[];
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

/** Create the shared comuni datalist once (full list from server when available). */
function ensureComuni(): void {
    if (document.getElementById('comuni-datalist')) return;
    const names = window.COMUNI_VALIDI ?? ITALIAN_COMUNI_FALLBACK;
    const dl = document.createElement('datalist');
    dl.id = 'comuni-datalist';
    names.forEach((name) => {
        const opt = document.createElement('option');
        opt.value = name;
        dl.appendChild(opt);
    });
    document.body.appendChild(dl);
}

/** Lazy-built Set of lowercase valid comune names for O(1) lookups. */
let _validSet: Set<string> | null = null;
function getValidSet(): Set<string> {
    if (_validSet) return _validSet;
    const names = window.COMUNI_VALIDI ?? ITALIAN_COMUNI_FALLBACK;
    _validSet = new Set(names.map((n) => n.toLowerCase()));
    return _validSet;
}

/** Update setCustomValidity on a municipality input based on current country. */
function validateMunicipality(input: HTMLInputElement, countryCode: string): void {
    const val = input.value.trim();
    if (countryCode !== 'IT' || val === '') {
        input.setCustomValidity('');
        return;
    }
    const valid = getValidSet().has(val.toLowerCase());
    input.setCustomValidity(
        valid ? '' : `"${val}" non è un comune italiano valido. Selezionare il nome dalla lista.`,
    );
}

/**
 * Initialise birth-country → municipality/province logic for every guest row.
 * Scoped to the nearest `.a-card` so multi-guest forms work correctly.
 */
function initPeopleReportingFields(): void {
    document.querySelectorAll<HTMLSelectElement>('[data-reporting-birth-country]').forEach((countrySelect) => {
        const card = countrySelect.closest<HTMLElement>('.a-card') ?? document.documentElement;
        const municipalityInput = card.querySelector<HTMLInputElement>('[data-reporting-birth-municipality]');
        const provinceGroup     = card.querySelector<HTMLElement>('[data-birth-province-group]');

        if (!municipalityInput) return;

        function update(countryCode: string): void {
            const isItaly = countryCode === 'IT';

            if (provinceGroup) provinceGroup.style.display = isItaly ? '' : 'none';

            // Update the associated <label> text
            const label = municipalityInput!.labels?.[0] as HTMLLabelElement | undefined;
            if (label) {
                const hasAsterisk = label.textContent?.trimEnd().endsWith('*') ?? false;
                label.textContent = (isItaly ? 'Comune di nascita' : 'Città di nascita') + (hasAsterisk ? ' *' : '');
            }

            municipalityInput!.placeholder = isItaly ? 'Es: Genova' : 'Es: Monaco';

            if (isItaly) {
                ensureComuni();
                municipalityInput!.setAttribute('list', 'comuni-datalist');
            } else {
                municipalityInput!.removeAttribute('list');
            }

            // Re-run validation when country changes
            validateMunicipality(municipalityInput!, countryCode);
        }

        // Validate on blur so user sees the error immediately after leaving the field
        municipalityInput.addEventListener('blur', () => {
            validateMunicipality(municipalityInput, countrySelect.value);
        });
        // Clear validity while the user is typing so the browser tooltip doesn't flicker
        municipalityInput.addEventListener('input', () => {
            municipalityInput.setCustomValidity('');
        });

        update(countrySelect.value);

        countrySelect.addEventListener('change', () => {
            update(countrySelect.value);
            if (countrySelect.value !== 'IT') {
                municipalityInput!.value = '';
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
    document.querySelectorAll<HTMLSelectElement>('[data-reporting-issue-country]').forEach((issueCountrySelect) => {
        const card            = issueCountrySelect.closest<HTMLElement>('.a-card') ?? document.documentElement;
        const issuePlaceGroup = card.querySelector<HTMLElement>('[data-document-issue-place-group]');
        const issuePlaceInput = card.querySelector<HTMLInputElement>('[data-reporting-issue-municipality]');

        if (!issuePlaceGroup) return;

        function update(countryCode: string): void {
            const isItaly = countryCode === 'IT';
            issuePlaceGroup!.style.display = isItaly ? '' : 'none';
            if (issuePlaceInput) {
                if (isItaly) {
                    ensureComuni();
                    issuePlaceInput.setAttribute('list', 'comuni-datalist');
                    issuePlaceInput.placeholder = 'Es: Genova';
                } else {
                    issuePlaceInput.removeAttribute('list');
                    issuePlaceInput.value = '';
                }
                validateMunicipality(issuePlaceInput, countryCode);
            }
        }

        if (issuePlaceInput) {
            issuePlaceInput.addEventListener('blur', () => {
                validateMunicipality(issuePlaceInput, issueCountrySelect.value);
            });
            issuePlaceInput.addEventListener('input', () => {
                issuePlaceInput.setCustomValidity('');
            });
        }

        update(issueCountrySelect.value);

        issueCountrySelect.addEventListener('change', () => update(issueCountrySelect.value));
    });
}

export { initDocumentIssueFields };

