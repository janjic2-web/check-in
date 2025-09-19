<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    /** --------------------------------
     *  JWTSubject
     *  -------------------------------- */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /** --------------------------------
     *  Role & Status konstante
     *  -------------------------------- */
    public const ROLE_ADMIN          = 'admin';
    public const ROLE_FACILITY_ADMIN = 'facility_admin';
    public const ROLE_EMPLOYEE       = 'employee';
    public const ROLE_SUPERADMIN     = 'superadmin'; // koristiš samo ako želiš korisničkog superadmina

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $table = 'users';

    /** Masovna dodela */
    protected $fillable = [
        'company_id',
        'username',
        'email',
        'password',
        'name',
        'surname',
        'role',
        'phone',
        'employee_id',
        'status',
        'required_checkins_day',
        'required_checkins_week',
        'required_checkins_month',
        'required_checkins_year',
        'email_verified_at',
        'remember_token',
    ];

    /** Sakrivena polja u JSON-u */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts (uključuje i automatsko hashovanje lozinke) */
    protected function casts(): array
    {
        return [
            'company_id'              => 'integer',
            'required_checkins_day'   => 'integer',
            'required_checkins_week'  => 'integer',
            'required_checkins_month' => 'integer',
            'required_checkins_year'  => 'integer',
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed', // Laravel auto-hash
        ];
    }

    /** Global scope po company_id */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    /* =======================
     * Relacije
     * ======================= */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function facilities()
    {
        // pivot tabela: users_facilities (sa company_id u pivotu)
        return $this->belongsToMany(Facility::class, 'users_facilities', 'user_id', 'facility_id')
            ->withTimestamps()
            ->withPivot('company_id');
    }

    public function checkins()
    {
        return $this->hasMany(Checkin::class);
    }

    /* =======================
     * Atributi / Accessors
     * ======================= */

    /** Puno ime (name + surname) */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->name ?? '') . ' ' . ($this->surname ?? ''))
        );
    }

    /* =======================
     * Scope & Helper metode
     * ======================= */

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isFacilityAdmin(): bool
    {
        return $this->role === self::ROLE_FACILITY_ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Dodela facility-ja uz pivot company_id
     */
    public function assignFacilities(array $facilityIds, int $companyId): void
    {
        $sync = [];
        foreach ($facilityIds as $fid) {
            $sync[$fid] = ['company_id' => $companyId];
        }
        $this->facilities()->sync($sync);
    }
}
