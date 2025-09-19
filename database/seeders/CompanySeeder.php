<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\CompanyApiKey;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['display_name' => 'Demo Company'],
            [
                'status'            => 'Active',
                'allow_outside'     => true,
                'min_inout_gap_min' => 2,
                'expires_at'        => now()->addYears(10),
            ]
        );

        CompanyApiKey::updateOrCreate(
            ['key' => 'TEST-API-KEY-123456'],
            [
                'company_id'   => $company->id,
                'active'       => true,
                'last_used_at' => null,
            ]
        );
    }
}