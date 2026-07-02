<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * ISO 3166-1 alpha-2 → Alloggiati Web 9-digit code.
     * Source: PoliziaStatoAlloggiatiDriver (previously hardcoded).
     */
    private const ISO2_MAP = [
        'AF' => '100000301', // AFGHANISTAN
        'AL' => '100000201', // ALBANIA
        'DZ' => '100000401', // ALGERIA
        'AD' => '100000202', // ANDORRA
        'AO' => '100000402', // ANGOLA
        'AR' => '100000602', // ARGENTINA
        'AM' => '100000358', // ARMENIA
        'AU' => '100000701', // AUSTRALIA
        'AT' => '100000203', // AUSTRIA
        'AZ' => '100000359', // AZERBAIGIAN
        'BD' => '100000305', // BANGLADESH
        'BY' => '100000256', // BIELORUSSIA
        'BE' => '100000206', // BELGIO
        'BA' => '100000252', // BOSNIA ED ERZEGOVINA
        'BR' => '100000605', // BRASILE
        'BG' => '100000209', // BULGARIA
        'CA' => '100000509', // CANADA
        'CL' => '100000606', // CILE
        'CN' => '100000314', // CINA
        'CO' => '100000608', // COLOMBIA
        'KR' => '100000320', // COREA DEL SUD
        'HR' => '100000250', // CROAZIA
        'CY' => '100000315', // CIPRO
        'CZ' => '100000257', // REPUBBLICA CECA
        'DK' => '100000212', // DANIMARCA
        'EG' => '100000419', // EGITTO
        'AE' => '100000322', // EMIRATI ARABI UNITI
        'EE' => '100000247', // ESTONIA
        'FI' => '100000214', // FINLANDIA
        'FR' => '100000215', // FRANCIA
        'GE' => '100000360', // GEORGIA
        'DE' => '100000216', // GERMANIA
        'GH' => '100000423', // GHANA
        'GR' => '100000220', // GRECIA
        'HU' => '100000244', // UNGHERIA
        'IS' => '100000223', // ISLANDA
        'IN' => '100000330', // INDIA
        'ID' => '100000331', // INDONESIA
        'IR' => '100000332', // IRAN
        'IQ' => '100000333', // IRAQ
        'IE' => '100000221', // IRLANDA
        'IL' => '100000334', // ISRAELE
        'IT' => '100000100', // ITALIA
        'JP' => '100000326', // GIAPPONE
        'KE' => '100000428', // KENYA
        'XK' => '100001002', // KOSOVO
        'LV' => '100000248', // LETTONIA
        'LI' => '100000225', // LIECHTENSTEIN
        'LT' => '100000249', // LITUANIA
        'LU' => '100000226', // LUSSEMBURGO
        'MK' => '100000997', // MACEDONIA DEL NORD
        'MY' => '100000767', // MALAYSIA
        'MT' => '100000227', // MALTA
        'MA' => '100000436', // MAROCCO
        'MX' => '100000527', // MESSICO
        'MD' => '100000254', // MOLDAVIA
        'MC' => '100000229', // MONACO
        'ME' => '100001001', // MONTENEGRO
        'NL' => '100000232', // PAESI BASSI
        'NZ' => '100000719', // NUOVA ZELANDA
        'NG' => '100000443', // NIGERIA
        'NO' => '100000231', // NORVEGIA
        'PK' => '100000344', // PAKISTAN
        'PE' => '100000615', // PERU
        'PH' => '100000323', // FILIPPINE
        'PL' => '100000233', // POLONIA
        'PT' => '100000234', // PORTOGALLO
        'GB' => '100000219', // REGNO UNITO
        'RO' => '100000235', // ROMANIA
        'RU' => '100000245', // FEDERAZIONE RUSSA
        'SM' => '100000236', // SAN MARINO
        'SA' => '100000302', // ARABIA SAUDITA
        'SN' => '100000450', // SENEGAL
        'RS' => '100001000', // SERBIA
        'SG' => '100000346', // SINGAPORE
        'SK' => '100000255', // REPUBBLICA SLOVACCA
        'SI' => '100000251', // SLOVENIA
        'ZA' => '100000454', // SUDAFRICA
        'ES' => '100000239', // SPAGNA
        'SE' => '100000240', // SVEZIA
        'CH' => '100000241', // SVIZZERA
        'TH' => '100000349', // THAILANDIA
        'TN' => '100000460', // TUNISIA
        'TR' => '100000351', // TURCHIA
        'UA' => '100000243', // UCRAINA
        'US' => '100000536', // STATI UNITI D'AMERICA
        'VA' => '100000246', // STATO DELLA CITTA DEL VATICANO
        'VE' => '100000619', // VENEZUELA
        'VN' => '100000353', // VIETNAM
    ];

    public function run(): void
    {
        Country::truncate();

        // Invert map: alloggiati_code => iso2
        $codeToIso2 = array_flip(self::ISO2_MAP);

        $csvPath = resource_path('data/AlloggiatiWeb/stati.csv');
        $fh = fopen($csvPath, 'r');

        // Skip header: Codice,Descrizione,Provincia,DataFineVal
        fgetcsv($fh);

        $rows = [];
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $alloggiatiCode = trim($row[0]);
            $nameIt         = mb_convert_case(trim($row[1]), MB_CASE_TITLE, 'UTF-8');
            $iso2           = $codeToIso2[$alloggiatiCode] ?? null;

            $rows[] = [
                'iso2'            => $iso2,
                'name_it'         => $nameIt,
                'alloggiati_code' => $alloggiatiCode,
            ];
        }

        fclose($fh);

        foreach (array_chunk($rows, 100) as $chunk) {
            Country::insert($chunk);
        }
    }
}
