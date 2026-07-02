/**
 * People reporting fields — conditional UI for guest reporting data.
 *
 * Works for both the single-person edit form (one instance per page) and the
 * multi-guest reporting form (N instances per page).  All lookups are scoped to
 * the nearest `.a-card` ancestor so each guest row is independent.
 *
 * Birth country logic:
 *  - IT  → show birth_province field; attach datalist for municipality
 *  - other → hide birth_province; plain text municipality; clear value
 *
 * Document issue country logic:
 *  - IT  → show document_issue_place (Italian municipality needed for Alloggiati code)
 *  - other → hide document_issue_place (country code alone is sufficient)
 */

// Lightweight list of Italian capoluogo names (enough for the datalist suggestions).
const ITALIAN_COMUNI: string[] = [
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

/** Create the shared comuni datalist once and attach it to the document body. */
function ensureComuni(): void {
    if (document.getElementById('comuni-datalist')) return;
    const dl = document.createElement('datalist');
    dl.id = 'comuni-datalist';
    ITALIAN_COMUNI.forEach((name) => {
        const opt = document.createElement('option');
        opt.value = name;
        dl.appendChild(opt);
    });
    document.body.appendChild(dl);
}

/**
 * Initialise birth-country → municipality/province logic for every guest row.
 * Scoped to the nearest `.a-card` so multi-guest forms work correctly.
 * Looks for:
 *   [data-reporting-birth-country]   — the country <select>
 *   [data-reporting-birth-municipality] — the municipality <input>
 *   [data-birth-province-group]      — wrapper div to show/hide province field
 */
function initPeopleReportingFields(): void {
    document.querySelectorAll<HTMLSelectElement>('[data-reporting-birth-country]').forEach((countrySelect) => {
        // Scope to the nearest card so each guest row is independent.
        const card = countrySelect.closest<HTMLElement>('.a-card') ?? document.documentElement;
        const municipalityInput = card.querySelector<HTMLInputElement>('[data-reporting-birth-municipality]');
        const provinceGroup     = card.querySelector<HTMLElement>('[data-birth-province-group]');

        if (!municipalityInput) return;

        function update(countryCode: string): void {
            const isItaly = countryCode === 'IT';

            if (provinceGroup) provinceGroup.style.display = isItaly ? '' : 'none';

            // Update the associated <label> text if present
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
        }

        // Set initial state
        update(countrySelect.value);

        countrySelect.addEventListener('change', () => {
            update(countrySelect.value);
            // Clear stale Italian comune name when switching away from IT
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
 * Looks for:
 *   [data-reporting-issue-country]      — the issue country <select>
 *   [data-document-issue-place-group]   — wrapper div to show/hide the place field
 *   [data-reporting-issue-municipality] — the issue place <input>
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
            }
        }

        update(issueCountrySelect.value);

        issueCountrySelect.addEventListener('change', () => update(issueCountrySelect.value));
    });
}

export { initDocumentIssueFields };
