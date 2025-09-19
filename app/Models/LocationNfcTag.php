<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationNfcTag extends Model
{
    use HasFactory;

    protected $table = 'location_nfc_tags';

    protected $fillable = [
        'location_id',
        'tag_uid',
        'description',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
