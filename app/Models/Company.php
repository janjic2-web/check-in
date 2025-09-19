<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Laravel\Cashier\Billable; // uključi kada povežeš Stripe Cashier

class Company extends Model
{
    use HasFactory, SoftDeletes; // , Billable;

    protected $table = 'companies';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'display_name',
        'legal_name',
        'vat_pib',
        'address',
        'city',
        'zip',
        'country',
        'timezone',
        'language',
        'status',
        'expires_at',
        'allow_outside',
        'default_radius_m',
        'anti_spam_min_interval',
        'offline_retention_hours',
        'min_inout_gap_min',
        'ble_min_rssi',
        'require_gps_checkin',
        // 'plan_code' // ako dodaš kolonu u bazi
    ];

    protected $casts = [
        'allow_outside'           => 'boolean',
        'default_radius_m'        => 'integer',
        'anti_spam_min_interval'  => 'integer',
        'offline_retention_hours' => 'integer',
        'min_inout_gap_min'       => 'integer',
        'ble_min_rssi'            => 'integer',
        'expires_at'              => 'datetime',
    ];

    // ===== Relacije =====

    public function apiKeys()
    {
        return $this->hasMany(CompanyApiKey::class);
    }

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function checkins()
    {
        return $this->hasMany(Checkin::class);
    }

    // Billing
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
