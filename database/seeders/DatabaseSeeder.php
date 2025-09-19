<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pozovi naš DevSeeder koji pravi test plan, kompaniju, facility,
        // lokacije, korisnike, API key i jedan checkin
        $this->call(DevSeeder::class);
        $this->call(UserSeeder::class);

    }
}
