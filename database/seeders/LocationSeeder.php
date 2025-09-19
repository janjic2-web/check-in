<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where("name","Demo Company")->first();
        if (!$company) return;

        Location::firstOrCreate(
            ["company_id" => $company->id, "name" => "HQ Gate"],
            ["active" => true, "lat" => 44.8125000, "lng" => 20.4612000, "radius_m" => 200, "outside_override" => "inherit"]
        );
    }
}