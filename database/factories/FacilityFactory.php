<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name'       => $this->faker->company . ' Facility',
            'address'    => $this->faker->streetAddress(),
            'city'       => $this->faker->city(),
            'zip'        => $this->faker->postcode(),
            'status'     => 'active',
        ];
    }
}
