<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GuestType;
use Illuminate\Database\Seeder;

class GuestTypesSeeder extends Seeder
{
    public function run(): void
    {
        GuestType::truncate();

        // Source: docs/AlloggiatiWeb/tipo_alloggiato.csv
        // requires_document: false for types 19 and 20 (family member / group member)
        $types = [
            ['code' => '16', 'name_it' => 'Ospite singolo',   'requires_document' => true],
            ['code' => '17', 'name_it' => 'Capo famiglia',    'requires_document' => true],
            ['code' => '18', 'name_it' => 'Capo gruppo',      'requires_document' => true],
            ['code' => '19', 'name_it' => 'Familiare',        'requires_document' => false],
            ['code' => '20', 'name_it' => 'Membro gruppo',    'requires_document' => false],
        ];

        GuestType::insert($types);
    }
}
