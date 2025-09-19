<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * State: kompanija aktivna do određenog datuma.
     */
    public function activeUntil($date): self
    {
        return $this->state(fn () => ['expires_at' => $date]);
    }

    public function definition(): array
    {
        return [
            'display_name'             => $this->faker->company(),
            'legal_name'               => $this->faker->company() . ' LLC',
            'vat_pib'                  => $this->faker->optional()->bothify('PIB########'),
            'address'                  => $this->faker->streetAddress(),
            'city'                     => $this->faker->city(),
            'zip'                      => $this->faker->postcode(),
            'country'                  => 'RS',
            'timezone'                 => 'Europe/Belgrade',
            'language'                 => 'en',
            'status'                   => 'active',
            'expires_at'               => now()->addYear(),
            'allow_outside'            => true,
            'default_radius_m'         => 150,
            'anti_spam_min_interval'   => 2,
            'offline_retention_hours'  => 72,
            'min_inout_gap_min'        => 0,
            'ble_min_rssi'             => -80,
            'require_gps_checkin'      => false,
        ];
    }
}
