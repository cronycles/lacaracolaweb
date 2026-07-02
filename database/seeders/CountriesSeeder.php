<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /** ISO 3166-1 alpha-2 → international dial code (E.164 prefix). */
    private const DIAL_CODES = [
        'AF' => '+93',
        'AL' => '+355',
        'DZ' => '+213',
        'AD' => '+376',
        'AO' => '+244',
        'AR' => '+54',
        'AM' => '+374',
        'AU' => '+61',
        'AT' => '+43',
        'AZ' => '+994',
        'BD' => '+880',
        'BY' => '+375',
        'BE' => '+32',
        'BA' => '+387',
        'BR' => '+55',
        'BG' => '+359',
        'CA' => '+1',
        'CL' => '+56',
        'CN' => '+86',
        'CO' => '+57',
        'KR' => '+82',
        'HR' => '+385',
        'CY' => '+357',
        'CZ' => '+420',
        'DK' => '+45',
        'EG' => '+20',
        'AE' => '+971',
        'EE' => '+372',
        'FI' => '+358',
        'FR' => '+33',
        'GE' => '+995',
        'DE' => '+49',
        'GH' => '+233',
        'GR' => '+30',
        'HU' => '+36',
        'IS' => '+354',
        'IN' => '+91',
        'ID' => '+62',
        'IR' => '+98',
        'IQ' => '+964',
        'IE' => '+353',
        'IL' => '+972',
        'IT' => '+39',
        'JP' => '+81',
        'KE' => '+254',
        'XK' => '+383',
        'LV' => '+371',
        'LI' => '+423',
        'LT' => '+370',
        'LU' => '+352',
        'MK' => '+389',
        'MY' => '+60',
        'MT' => '+356',
        'MA' => '+212',
        'MX' => '+52',
        'MD' => '+373',
        'MC' => '+377',
        'ME' => '+382',
        'NL' => '+31',
        'NZ' => '+64',
        'NG' => '+234',
        'NO' => '+47',
        'PK' => '+92',
        'PE' => '+51',
        'PH' => '+63',
        'PL' => '+48',
        'PT' => '+351',
        'GB' => '+44',
        'RO' => '+40',
        'RU' => '+7',
        'SM' => '+378',
        'SA' => '+966',
        'SN' => '+221',
        'RS' => '+381',
        'SG' => '+65',
        'SK' => '+421',
        'SI' => '+386',
        'ZA' => '+27',
        'ES' => '+34',
        'SE' => '+46',
        'CH' => '+41',
        'TH' => '+66',
        'TN' => '+216',
        'TR' => '+90',
        'UA' => '+380',
        'US' => '+1',
        'VA' => '+379',
        'VE' => '+58',
        'VN' => '+84',
    ];

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
                'dial_code'       => $iso2 !== null ? (self::DIAL_CODES[$iso2] ?? null) : null,
            ];
        }

        fclose($fh);

        foreach (array_chunk($rows, 100) as $chunk) {
            Country::insert($chunk);
        }
    }
}
