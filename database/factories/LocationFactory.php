<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $facility = \App\Models\Facility::factory()->create();
        return [
            'company_id' => $facility->company_id,
            'facility_id' => $facility->id,
            'name' => $this->faker->word(),
            'lat' => $this->faker->randomFloat(6, 44.7860, 44.7870),
            'lng' => $this->faker->randomFloat(6, 20.4480, 20.4500),
            'active' => true,
        ];
    }
}
