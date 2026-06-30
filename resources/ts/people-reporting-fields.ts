/**
 * People reporting fields — conditional UI for guest reporting data.
 *
 * When birth_country_code changes:
 *  - IT  → show birth_province field; optionally hint for municipality
 *  - other → hide birth_province field; plain text municipality
 */

interface MunicipalityEntry {
    name: string;
    code: string;
}

// Lightweight list built from ItalianMunicipalities::all() keys (names only — no codes needed client-side)
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

function initPeopleReportingFields(): void {
    const birthCountrySelect = document.querySelector<HTMLSelectElement>('[data-reporting-birth-country]');
    const birthMunicipalityInput = document.querySelector<HTMLInputElement>('[data-reporting-birth-municipality]');
    const birthProvinceGroup = document.getElementById('birth_province_group');

    if (!birthCountrySelect || !birthMunicipalityInput) return;

    function updateFieldsForCountry(countryCode: string): void {
        const isItaly = countryCode === 'IT';

        // Show/hide province group
        if (birthProvinceGroup) {
            birthProvinceGroup.style.display = isItaly ? '' : 'none';
        }

        // Update municipality placeholder
        birthMunicipalityInput!.placeholder = isItaly ? 'Es: Genova' : 'Città di nascita';

        // Add/remove datalist for Italian comuni
        const existingDatalist = document.getElementById('comuni-datalist');
        if (isItaly) {
            if (!existingDatalist) {
                const dl = document.createElement('datalist');
                dl.id = 'comuni-datalist';
                ITALIAN_COMUNI.forEach((name) => {
                    const opt = document.createElement('option');
                    opt.value = name;
                    dl.appendChild(opt);
                });
                document.body.appendChild(dl);
            }
            birthMunicipalityInput!.setAttribute('list', 'comuni-datalist');
        } else {
            birthMunicipalityInput!.removeAttribute('list');
        }
    }

    // Initial state
    updateFieldsForCountry(birthCountrySelect.value);

    // On change
    birthCountrySelect.addEventListener('change', () => {
        updateFieldsForCountry(birthCountrySelect.value);
        // Clear municipality when switching away from IT to avoid stale commune names
        if (birthCountrySelect.value !== 'IT') {
            const currentValue = birthMunicipalityInput!.getAttribute('data-current-value') ?? '';
            // Only clear if it was an Italian comune (simple heuristic: clear always on country change)
            birthMunicipalityInput!.value = '';
        }
    });
}

export { initPeopleReportingFields };
