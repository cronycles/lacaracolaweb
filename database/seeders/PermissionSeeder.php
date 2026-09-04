<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_bookings',    'description' => 'Visualizza lista e dettaglio prenotazioni (read-only, senza income_amount)'],
            ['name' => 'manage_bookings',  'description' => 'Crea, modifica, elimina e cancella prenotazioni'],
            ['name' => 'import_pdf',       'description' => 'Importa prenotazioni da PDF Interhome'],
            ['name' => 'view_people',      'description' => 'Visualizza lista e dettaglio ospiti (read-only)'],
            ['name' => 'manage_people',    'description' => 'Crea, modifica, elimina ospiti'],
            ['name' => 'view_calendar',    'description' => 'Visualizza calendario disponibilità (read-only)'],
            ['name' => 'manage_calendar',  'description' => 'Crea ed elimina blocchi disponibilità'],
            ['name' => 'view_accounting',  'description' => 'Accede alla contabilità, al campo income_amount e al widget finanziario nella dashboard'],
            ['name' => 'manage_pricing',   'description' => 'Gestisce regole prezzi e sconti soggiorno'],
            ['name' => 'manage_settings',  'description' => 'Accede e modifica le impostazioni generali'],
            ['name' => 'manage_newsletter', 'description' => 'Gestisce le iscrizioni alla newsletter'],
            ['name' => 'manage_users',     'description' => 'Gestisce gli utenti admin (non delegabile via override)'],
            ['name' => 'manage_reviews',   'description' => 'Gestisce le recensioni pubbliche'],
        ];

        foreach ($permissions as $data) {
            Permission::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
