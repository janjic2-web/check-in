<?php
namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\Company;
use App\Models\User;
use App\Models\Facility;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttendanceSessionFactory extends Factory
{
    protected $model = AttendanceSession::class;

    public function definition()
    {
        $inAt = $this->faker->dateTimeBetween('-5 days', 'now');
        $outAt = $this->faker->boolean(70) ? $this->faker->dateTimeBetween($inAt, 'now') : null;
        return [
            'company_id'      => Company::factory(),
            'user_id'         => User::factory(),
            'facility_id'     => Facility::factory(),
            'location_id'     => Location::factory(),
            'in_at'           => $inAt,
            'out_at'          => $outAt,
            'duration_sec'    => $outAt ? $outAt->getTimestamp() - $inAt->getTimestamp() : null,
            'status'          => $outAt ? 'closed' : 'open',
            'under_threshold' => $this->faker->boolean(20),
            'in_meta'         => ['method' => $this->faker->randomElement(['manual','nfc','ble','qr']), 'device_id' => Str::uuid()],
            'out_meta'        => $outAt ? ['method' => $this->faker->randomElement(['manual','nfc','ble','qr']), 'device_id' => Str::uuid()] : null,
        ];
    }
}
