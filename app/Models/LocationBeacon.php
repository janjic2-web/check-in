<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationBeacon extends Model
{
    use HasFactory;

    protected $table = 'location_beacons';

    protected $fillable = [
        'company_id',
        'location_id',
        'uuid',
        'major',
        'minor',
        'label',
        'tx_power_1m',
        'active',
    ];

    protected $casts = [
        'company_id'  => 'integer',
        'location_id' => 'integer',
        'major'       => 'integer',
        'minor'       => 'integer',
        'tx_power_1m' => 'integer',
        'active'      => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    // Relacije
    public function company()  { return $this->belongsTo(Company::class); }
    public function location() { return $this->belongsTo(Location::class); }
}
