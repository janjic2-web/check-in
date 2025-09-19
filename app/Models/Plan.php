<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $fillable = [
        'code',              // free|starter|pro|enterprise
        'name',
        'max_users',         // null = unlimited
        'features',          // json, npr {"facility_admin":true}
        'price_month_cents',
        'price_year_cents',
        'currency',
        'active',
    ];

    protected $casts = [
        'max_users'          => 'integer',
        'features'           => 'array',
        'price_month_cents'  => 'integer',
        'price_year_cents'   => 'integer',
        'active'             => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
