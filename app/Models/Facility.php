<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'facilities';

    protected $fillable = [
        'company_id',
        'name',
        'lat',
        'lng',
        'default_radius_m',
        'outside_override', // enum: inherit|disallow
        'active',
        'address',
        'city',
        // 'zip' // dodaj kad uvedeš kolonu
    ];

    protected $casts = [
        'active'            => 'boolean',
        'default_radius_m'  => 'integer',
        'lat'               => 'decimal:7',
        'lng'               => 'decimal:7',
        'outside_override'  => 'string',
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

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Korisnici dodeljeni ovom facility-ju (pivot users_facilities)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_facilities')
            ->withTimestamps()
            ->withPivot('company_id');
    }

    /**
     * Checkin zapisi koji pripadaju ovom facility-ju
     */
    public function checkins()
    {
        return $this->hasMany(Checkin::class);
    }
}
