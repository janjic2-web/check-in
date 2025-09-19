<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkin extends Model
{
    use HasFactory;

    protected $table = 'checkins';

    // Enumi po specifikaciji
    public const METHOD_NFC = 'nfc';
    public const METHOD_BLE = 'ble';
    public const METHOD_QR  = 'qr';

    public const ACTION_IN  = 'in';
    public const ACTION_OUT = 'out';

    public const STATUS_INSIDE  = 'inside';
    public const STATUS_OUTSIDE = 'outside';

    protected $fillable = [
        'company_id',
        'facility_id',
        'location_id',
        'user_id',
        'method',
        'action',
        'status',
        'distance_m',
        'lat',
        'lng',
        'details',
        'device_id',
        'platform',
        'app_version',
        'client_event_id',
    ];

    protected $casts = [
        'company_id'   => 'integer',
        'facility_id'  => 'integer',
        'location_id'  => 'integer',
        'user_id'      => 'integer',
        'distance_m'   => 'decimal:2',
        'lat'          => 'decimal:7',
        'lng'          => 'decimal:7',
        'details'      => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    // ===== Relacije =====

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
