<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'locations';

    /**
     * Masovno dodeljiva polja (u skladu sa specifikacijom)
     */
    protected $fillable = [
        'company_id',
        'facility_id',
        'name',
        'lat',
        'lng',
        'radius_m',
        'outside_override',           // enum: inherit|disallow
        // GPS zahtevi po metodi:
        'require_gps_nfc',
        'require_gps_ble',
        'require_gps_qr',
        // BLE prag na nivou lokacije (strožije od company/facility)
        'min_rssi_override',          // nullable int
        // KPI ciljevi:
        'required_visits_day',
        'required_visits_week',
        'required_visits_month',
        'required_visits_year',
        'active',
    ];

    /**
     * Cast-ovi
     */
    protected $casts = [
        'company_id'            => 'integer',
        'facility_id'           => 'integer',
        'lat'                   => 'decimal:7',
        'lng'                   => 'decimal:7',
        'radius_m'              => 'integer',
        'outside_override'      => 'string',
        'require_gps_nfc'       => 'boolean',
        'require_gps_ble'       => 'boolean',
        'require_gps_qr'        => 'boolean',
        'min_rssi_override'     => 'integer',
        'required_visits_day'   => 'integer',
        'required_visits_week'  => 'integer',
        'required_visits_month' => 'integer',
        'required_visits_year'  => 'integer',
        'active'                => 'boolean',
    ];

    /**
     * Global tenant filter (company_id iz CompanyApiKey middleware-a)
     */
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

    /**
     * NFC tagovi dodeljeni lokaciji
     */

        /**
         * NFC tagovi dodeljeni lokaciji (više tagova)
         */
        public function nfcTags()
        {
            return $this->hasMany(LocationNfcTag::class);
        }

        /**
         * BLE beacon-i dodeljeni lokaciji (više beacona)
         */
        public function bleBeacons()
        {
            return $this->hasMany(LocationBleBeacon::class);
        }

        /**
         * QR kodovi dodeljeni lokaciji (više QR kodova)
         */
        public function qrCodes()
        {
            return $this->hasMany(LocationQrCode::class);
        }

    /**
     * Checkin zapisi na ovoj lokaciji
     */
    public function checkins()
    {
        return $this->hasMany(Checkin::class);
    }

    // ===== Scopes / helperi =====

    /**
     * Samo aktivne lokacije
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Filtriraj po facility-ju
     */
    public function scopeForFacility($query, int $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }
}
