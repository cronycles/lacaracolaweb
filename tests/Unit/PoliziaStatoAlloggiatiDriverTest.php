<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Country;
use App\Services\GuestReporting\GuestRecord;
use App\Services\GuestReporting\PoliziaStatoAlloggiatiDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PoliziaStatoAlloggiatiDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_array_item_contains_only_the_168_character_record(): void
    {
        Country::create([
            'iso2'            => 'FR',
            'name_it'         => 'Francia',
            'alloggiati_code' => '201000200',
        ]);

        $guest = new GuestRecord(
            tipoAlloggiato: '20',
            arrivalDate: '10/08/2026',
            stayNights: 2,
            lastName: 'Rossi',
            firstName: 'Mario',
            gender: 'M',
            birthDate: '1990-01-01',
            birthMunicipality: 'Paris',
            birthProvince: null,
            birthCountryCode: 'FR',
            nationalityCode: 'FR',
            documentType: '',
            documentNumber: '',
            documentIssuePlace: '',
            documentIssueCountryCode: '',
        );

        $driver = new PoliziaStatoAlloggiatiDriver([]);
        $buildElenco = new ReflectionMethod($driver, 'buildElenco');
        $records = $buildElenco->invoke($driver, [$guest, $guest]);

        self::assertSame([168, 168], array_map('strlen', $records['string']));
        self::assertStringNotContainsString("\r\n", $records['string'][0]);
        self::assertStringNotContainsString("\r\n", $records['string'][1]);
    }

    public function test_free_text_is_normalized_to_printable_ascii(): void
    {
        Country::create([
            'iso2'            => 'FR',
            'name_it'         => 'Francia',
            'alloggiati_code' => '201000200',
        ]);

        $guest = new GuestRecord(
            tipoAlloggiato: '18',
            arrivalDate: '10/08/2026',
            stayNights: 2,
            lastName: "Àlvarez Ñørgaard ç ¢ 😀",
            firstName: "Zoë Łukáš",
            gender: 'F',
            birthDate: '1990-01-01',
            birthMunicipality: 'Paris',
            birthProvince: null,
            birthCountryCode: 'FR',
            nationalityCode: 'FR',
            documentType: 'passport',
            documentNumber: "AB¢-ç😀123",
            documentIssuePlace: '',
            documentIssueCountryCode: 'FR',
        );

        $driver = new PoliziaStatoAlloggiatiDriver([]);
        $buildRecord = new ReflectionMethod($driver, 'buildRecord');
        $record = $buildRecord->invoke($driver, $guest);

        self::assertSame(168, strlen($record));
        self::assertDoesNotMatchRegularExpression('/[^\x20-\x7E]/', $record);
        self::assertSame('ALVAREZ NORGAARD C C', rtrim(substr($record, 14, 50)));
        self::assertSame('ZOE LUKAS', rtrim(substr($record, 64, 30)));
    }
}
