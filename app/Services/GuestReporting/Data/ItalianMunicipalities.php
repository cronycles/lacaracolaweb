<?php

declare(strict_types=1);

namespace App\Services\GuestReporting\Data;

/**
 * Lookup table: normalized Italian municipality name → Codice Belfiore (4 chars).
 *
 * This file contains the ~80 most common Italian municipalities.
 * For a full list (~8 000 comuni), replace the array below with a complete
 * dataset from https://www.istat.it/it/archivio/6789 or similar official source.
 *
 * Keys are lowercase, accent-normalized municipality names.
 * Values are 4-char Codice Belfiore (used by Alloggiati Web).
 *
 * IMPORTANT: Verify these codes against the official Alloggiati Web documentation
 * before using in production.
 */
class ItalianMunicipalities
{
    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            'agrigento'       => 'A089',
            'alessandria'     => 'A182',
            'ancona'          => 'A271',
            'andora'          => 'A278',
            'aosta'           => 'A326',
            'arezzo'          => 'A390',
            'ascoli piceno'   => 'A462',
            'asti'            => 'A479',
            'avellino'        => 'A509',
            'bari'            => 'A662',
            'barletta'        => 'A669',
            'belluno'         => 'A757',
            'benevento'       => 'A783',
            'bergamo'         => 'A794',
            'biella'          => 'A859',
            'bologna'         => 'A944',
            'bolzano'         => 'A952',
            'brescia'         => 'B157',
            'brindisi'        => 'B180',
            'cagliari'        => 'B354',
            'caltanissetta'   => 'B429',
            'campobasso'      => 'B519',
            'caserta'         => 'B963',
            'catania'         => 'C351',
            'catanzaro'       => 'C352',
            'chieti'          => 'C632',
            'como'            => 'C933',
            'cosenza'         => 'D086',
            'cremona'         => 'D150',
            'crotone'         => 'D122',
            'cuneo'           => 'D205',
            'enna'            => 'C342',
            'fermo'           => 'D542',
            'ferrara'         => 'D548',
            'firenze'         => 'D612',
            'foggia'          => 'D643',
            'forli'           => 'D704',
            'frosinone'       => 'D810',
            'genova'          => 'D969',
            'gorizia'         => 'E098',
            'grosseto'        => 'E202',
            'imperia'         => 'E290',
            'isernia'         => 'E335',
            "l'aquila"        => 'A345',
            'la spezia'       => 'E463',
            'latina'          => 'E472',
            'lecce'           => 'E492',
            'lecco'           => 'E498',
            'livorno'         => 'E625',
            'lodi'            => 'E648',
            'lucca'           => 'E715',
            'macerata'        => 'E783',
            'mantova'         => 'E897',
            'massa'           => 'F023',
            'matera'          => 'F052',
            'messina'         => 'F158',
            'milano'          => 'F205',
            'modena'          => 'F257',
            'monza'           => 'F704',
            'napoli'          => 'F839',
            'novara'          => 'F952',
            'nuoro'           => 'F979',
            'oristano'        => 'G113',
            'padova'          => 'G224',
            'palermo'         => 'G273',
            'parma'           => 'G337',
            'pavia'           => 'G388',
            'perugia'         => 'G478',
            'pesaro'          => 'G479',
            'pescara'         => 'G482',
            'piacenza'        => 'G535',
            'pisa'            => 'G702',
            'pistoia'         => 'G713',
            'pordenone'       => 'G888',
            'potenza'         => 'G942',
            'prato'           => 'G999',
            'ragusa'          => 'H163',
            'ravenna'         => 'H199',
            'reggio calabria' => 'H224',
            'reggio emilia'   => 'H223',
            'rieti'           => 'H282',
            'rimini'          => 'H294',
            'roma'            => 'H501',
            'rovigo'          => 'H620',
            'salerno'         => 'H703',
            'sassari'         => 'I452',
            'savona'          => 'I480',
            'siena'           => 'I726',
            'siracusa'        => 'I754',
            'sondrio'         => 'I829',
            'sud sardegna'    => 'M208',
            'taranto'         => 'L049',
            'teramo'          => 'L103',
            'terni'           => 'L117',
            'torino'          => 'L219',
            'trapani'         => 'L331',
            'trento'          => 'L378',
            'treviso'         => 'L407',
            'trieste'         => 'L424',
            'udine'           => 'L483',
            'varese'          => 'L682',
            'venezia'         => 'L736',
            'verbania'        => 'L746',
            'vercelli'        => 'L750',
            'verona'          => 'L781',
            'vibo valentia'   => 'F537',
            'vicenza'         => 'L840',
            'viterbo'         => 'M082',
        ];
    }

    /**
     * Look up the Codice Belfiore for a municipality name.
     * Normalizes input (lowercase, trim) before lookup.
     */
    public static function findCode(string $municipalityName): ?string
    {
        $normalized = mb_strtolower(trim($municipalityName));

        return self::all()[$normalized] ?? null;
    }
}
