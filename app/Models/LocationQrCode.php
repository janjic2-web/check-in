<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationQrCode extends Model
{
    use HasFactory;

    protected $table = 'location_qr_codes';

    protected $fillable = [
        'location_id',
        'qr_payload',
        'description',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
