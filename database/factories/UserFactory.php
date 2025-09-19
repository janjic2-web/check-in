<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    // >>> PAZI: ovo sme da postoji samo jednom <<<
    protected $model = User::class;

    public function definition(): array
    {
        return [
            // multi-tenant veza (zahteva CompanyFactory)
            'company_id' => Company::factory(),

            // auth + identitet
            'username'          => $this->faker->unique()->userName(),
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            // unosi se plain; pretpostavka: u User modelu imaš mutator koji hešuje
            'password'          => 'password',
            'name'              => $this->faker->firstName(),
            'surname'           => $this->faker->lastName(),
            'role'              => 'employee', // 'admin' | 'facility_admin' | 'employee'
            'phone'             => $this->faker->optional()->e164PhoneNumber(),
            'employee_id'       => $this->faker->optional()->bothify('EMP-####'),
            'status'            => 'active',

            // KPI defaulti
            'required_checkins_day'   => 0,
            'required_checkins_week'  => 0,
            'required_checkins_month' => 0,
            'required_checkins_year'  => 0,

            'remember_token' => Str::random(10),
        ];
    }

    // ——— Stanja (opciono, korisno u testovima) ———

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function facilityAdmin(): static
    {
        return $this->state(fn () => ['role' => 'facility_admin']);
    }

    public function employee(): static
    {
        return $this->state(fn () => ['role' => 'employee']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }
}
