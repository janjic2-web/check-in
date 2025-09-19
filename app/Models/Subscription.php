<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';

    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_FREE     = 'free';

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'period_start',
        'period_end',
        'trial_end',
        'cancel_at',
        'provider_sub_id',
        'provider_customer_id',
        // opciono: 'cycle' => 'monthly'|'yearly'
    ];

    protected $casts = [
        'company_id'   => 'integer',
        'plan_id'      => 'integer',
        'period_start' => 'datetime',
        'period_end'   => 'datetime',
        'trial_end'    => 'datetime',
        'cancel_at'    => 'datetime',
    ];
}
