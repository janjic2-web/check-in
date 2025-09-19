<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\Facility;
use App\Models\User;

class TestEntities extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_test_entities()
    {
        $company = Company::create([
            'display_name' => 'Test Kompanija',
            'legal_name' => 'Test Kompanija DOO',
            'vat_pib' => '12345678',
            'address' => 'Test Adresa 1',
            'city' => 'Beograd',
            'zip' => '11000',
            'country' => 'RS',
            'timezone' => 'Europe/Belgrade',
            'language' => 'sr',
            'status' => 'active',
        ]);

        $facility = Facility::create([
            'company_id' => $company->id,
            'name' => 'Test Facility',
            'lat' => 44.7866,
            'lng' => 20.4489,
            'default_radius_m' => 150,
            'outside_override' => 'inherit',
            'active' => true,
            'status' => 'active',
            'address' => 'Test Facility Adresa',
            'zip' => '11000',
        ]);

        $adminUser = User::create([
            'company_id' => $company->id,
            'username' => 'admin1',
            'email' => 'admin@company.com',
            'password' => bcrypt('12345678'),
            'name' => 'Admin',
            'surname' => 'Company',
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        $facilityAdminUser = User::create([
            'company_id' => $company->id,
            'username' => 'facadmin1',
            'email' => 'facadmin@company.com',
            'password' => bcrypt('12345678'),
            'name' => 'Facility',
            'surname' => 'Admin',
            'role' => 'facility_admin',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'company_id' => $company->id,
            'username' => 'user1',
            'email' => 'user@company.com',
            'password' => bcrypt('12345678'),
            'name' => 'User',
            'surname' => 'Employee',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('companies', ['display_name' => 'Test Kompanija']);
        $this->assertDatabaseHas('facilities', ['name' => 'Test Facility']);
        $this->assertDatabaseHas('users', ['username' => 'admin1', 'role' => 'company_admin']);
        $this->assertDatabaseHas('users', ['username' => 'facadmin1', 'role' => 'facility_admin']);
        $this->assertDatabaseHas('users', ['username' => 'user1', 'role' => 'employee']);
    }
}
