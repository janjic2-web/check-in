<?php

namespace Database\Factories;

use App\Models\LocationBeacon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationBeaconFactory extends Factory
{
    protected $model = LocationBeacon::class;

    public function definition(): array
    {
        return [
            'company_id' => 1, // override in test
            'location_id' => 1, // override in test
            'uuid' => $this->faker->uuid,
            'major' => $this->faker->numberBetween(1, 100),
            'minor' => $this->faker->numberBetween(1, 100),
            'active' => true,
        ];
    }
}
