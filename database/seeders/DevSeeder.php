<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\User;
use App\Models\Facility;
use App\Models\Location;
use App\Models\LocationTag;
use App\Models\LocationBeacon;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------
        // 0) Čišćenje baze (redosled zbog FK)
        // -------------------------------------------
        Schema::disableForeignKeyConstraints();

        // Ako želiš potpun reset bez ostavljanja istorije:
        DB::table('checkins')->truncate();
        DB::table('users_facilities')->truncate();
        DB::table('location_tags')->truncate();
        DB::table('location_beacons')->truncate();
        DB::table('locations')->truncate();
        DB::table('facilities')->truncate();
        DB::table('company_api_keys')->truncate();
    DB::table('users')->delete();
    DB::table('companies')->delete();

        Schema::enableForeignKeyConstraints();

        // -------------------------------------------
        // 1) Kreiraj test kompaniju
        // -------------------------------------------
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
            'status'                  => 'active',          // active|suspended|expired
            'expires_at'              => null,
            'allow_outside'           => true,
            'default_radius_m'        => 150,
            'anti_spam_min_interval'  => 2,
            'offline_retention_hours' => 72,
            'min_inout_gap_min'       => 0,
            'ble_min_rssi'            => -80,
            // QR HMAC tajna (32B random, base64url)
            'qr_secret'               => self::randomBase64Url(32),
        ]);

        // -------------------------------------------
        // 2) Test API ključ (superadmin) — x-api-key
        // -------------------------------------------
        CompanyApiKey::create([
            'company_id'     => $company->id,
            'key'            => 'TEST_API_KEY_123',
            'active'         => true,
            'is_superadmin'  => true,   // dozvoljava globalni uvid + impersonation (write traži target company_id)
            'last_used_at'   => null,
        ]);

        // -------------------------------------------
        // 3) Facility + Location
        // -------------------------------------------
        $facility = Facility::create([
            'company_id'        => $company->id,
            'name'              => 'HQ',
            'address'           => 'Bulevar Oslobođenja 1',
            'city'              => 'Novi Sad',
            'lat'               => 45.2671,
            'lng'               => 19.8335,
            'default_radius_m'  => 150,
            'outside_override'  => 'inherit',   // inherit|disallow
            'active'            => true,
        ]);

        $location = Location::create([
            'company_id'         => $company->id,
            'facility_id'        => $facility->id,
            'name'               => 'Main Entrance',
            'lat'                => 45.26710,
            'lng'                => 19.83350,
            'radius_m'           => 150,
            'outside_override'   => 'inherit',
            'require_gps_nfc'    => false,
            'require_gps_ble'    => false,
            'require_gps_qr'     => false,
            'min_rssi_override'  => null,       // koristi global/company default (-80)
            'active'             => true,
        ]);

        // -------------------------------------------
        // 4) NFC Tag + BLE Beacon (test fixtures)
        // -------------------------------------------
        LocationTag::create([
            'company_id'  => $company->id,
            'location_id' => $location->id,
            'tag_uid'     => 'TEST_TAG_123',
        ]);

        LocationBeacon::create([
            'company_id'  => $company->id,
            'location_id' => $location->id,
            'uuid'        => '123e4567-e89b-12d3-a456-426614174000',
            'major'       => 1,
            'minor'       => 1,
            'label'       => 'TEST_BEACON_1',
            'tx_power_1m' => -59,
            'active'      => true,
        ]);

        // -------------------------------------------
        // 5) Korisnici (facility admin + dodela u facility)
        //    Napomena: User model ima mutator za password (auto-hash).
        // -------------------------------------------
        $facilityAdmin = User::create([
            'company_id'  => $company->id,
            'username'    => 'facility',
            'email'       => 'facility@test.rs',
            'password'    => 'tajna123',      // plain; mutator će hešovati
            'name'        => 'Facility',
            'surname'     => 'Admin',
            'role'        => 'facility_admin', // admin|facility_admin|employee
            'phone'       => null,
            'employee_id' => 'FA-001',
            'status'      => 'active',
        ]);

        // assignment: users_facilities pivot
        DB::table('users_facilities')->insert([
            'company_id'  => $company->id,
            'user_id'     => $facilityAdmin->id,
            'facility_id' => $facility->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // (Opcioni dodatni nalozi — odkomentariši po potrebi)
        /*
        $companyAdmin = User::create([
            'company_id'  => $company->id,
            'username'    => 'companyadmin',
            'email'       => 'admin@test.rs',
            'password'    => 'tajna123',
            'name'        => 'Company',
            'surname'     => 'Admin',
            'role'        => 'admin',
            'status'      => 'active',
        ]);

        $employee = User::create([
            'company_id'  => $company->id,
            'username'    => 'emp1',
            'email'       => 'emp@test.rs',
            'password'    => 'tajna123',
            'name'        => 'Employee',
            'surname'     => 'Jedan',
            'role'        => 'employee',
            'status'      => 'active',
        ]);

        DB::table('users_facilities')->insert([
            [
                'company_id'  => $company->id,
                'user_id'     => $companyAdmin->id,
                'facility_id' => $facility->id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'company_id'  => $company->id,
                'user_id'     => $employee->id,
                'facility_id' => $facility->id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
        */

        // Gotovo
    }

    /**
     * Generiši base64url string (bez paddinga) dužine $bytes bajtova.
     */
    private static function randomBase64Url(int $bytes = 32): string
    {
        $bin = random_bytes($bytes);
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
