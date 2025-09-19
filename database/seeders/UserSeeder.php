<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyApiKey;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::transaction(function () {
                // 1) Privremeno isključi FK provere (MySQL: FOREIGN_KEY_CHECKS=0)
                Schema::disableForeignKeyConstraints();

                // 2) Truncate u bezbednom redosledu (najpre zavisne tabele, pa roditelji)
                DB::table('checkins')->truncate();          // ako postoji FK ka users/locations/companies
                DB::table('users_facilities')->truncate();  // pivot tabela ka users / facilities
                DB::table('users')->truncate();             // zavisi od companies
                DB::table('company_api_keys')->truncate();  // zavisi od companies
                DB::table('companies')->truncate();         // roditeljska tabela

                // 3) Vrati FK provere
                Schema::enableForeignKeyConstraints();

                // 4) Seed podaci (idempotentno gde ima smisla)
                $company = Company::create([
                    'display_name'            => 'Test Company',
                    'legal_name'              => 'Test Company LLC',
                    'vat_pib'                 => 'RS12345678',
                    'address'                 => 'Bulevar Oslobođenja 1',
                    'city'                    => 'Novi Sad',
                    'zip'                     => '21000',
                    'country'                 => 'RS',
                    'timezone'                => 'Europe/Belgrade',
                    'language'                => 'en',
                    'status'                  => 'active',
                    'expires_at'              => null,
                    'allow_outside'           => true,
                    'default_radius_m'        => 150,
                    'anti_spam_min_interval'  => 2,
                    'offline_retention_hours' => 72,
                    'min_inout_gap_min'       => 0,
                    'ble_min_rssi'            => -80,
                    'qr_secret'               => 'TEST_SECRET',
                ]);

                CompanyApiKey::updateOrInsert(
                    ['company_id' => $company->id, 'key' => 'TEST_API_KEY_123'],
                    ['active' => true, 'is_superadmin' => true]
                );

                User::create([
                    'company_id' => $company->id,
                    'username'   => 'user1',
                    'email'      => 'user1@test.rs',
                    'password'   => Hash::make('tajna123'), // VAŽNO: heširaj lozinku
                    'name'       => 'User',
                    'surname'    => 'Test',
                    'role'       => 'employee',
                    'status'     => 'active',
                ]);
            });
        } catch (\Exception $e) {
            echo "UserSeeder exception: " . $e->getMessage() . "\n";
        }
    }
}
