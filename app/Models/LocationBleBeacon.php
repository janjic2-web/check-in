<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationBleBeacon extends Model
{
    use HasFactory;

    protected $table = 'location_ble_beacons';

    protected $fillable = [
        'location_id',
        'beacon_id',
        'description',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
