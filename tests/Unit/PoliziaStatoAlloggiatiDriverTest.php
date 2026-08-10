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

    public function test_record_terminator_is_added_between_rows_only(): void
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

        self::assertSame([170, 168], array_map('strlen', $records['string']));
        self::assertStringEndsWith("\r\n", $records['string'][0]);
        self::assertStringNotContainsString("\r\n", $records['string'][1]);
    }
}
