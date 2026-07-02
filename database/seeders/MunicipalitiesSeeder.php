<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Municipality;
use DateTime;
use Illuminate\Database\Seeder;

class MunicipalitiesSeeder extends Seeder
{
    public function run(): void
    {
        Municipality::truncate();

        $csvPath = resource_path('data/AlloggiatiWeb/comuni.csv');
        $fh = fopen($csvPath, 'r');

        // Skip header: Codice,Descrizione,Provincia,DataFineVal
        fgetcsv($fh);

        $rows = [];
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $dataFineVal = trim($row[3] ?? '');
            $expiresAt   = null;

            if ($dataFineVal !== '') {
                $dt = DateTime::createFromFormat('d/m/Y H:i:s', $dataFineVal)
                    ?: DateTime::createFromFormat('d/m/Y', $dataFineVal);
                if ($dt) {
                    $expiresAt = $dt->format('Y-m-d');
                }
            }

            $rows[] = [
                'code'       => trim($row[0]),
                'name'       => trim($row[1]),
                'province'   => trim($row[2]),
                'expires_at' => $expiresAt,
            ];

            // Flush in chunks of 500 to avoid memory issues with 11k rows
            if (count($rows) === 500) {
                Municipality::insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            Municipality::insert($rows);
        }

        fclose($fh);
    }
}
