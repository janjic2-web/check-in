<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderEvent extends Model
{
    use HasFactory;

    protected $table = 'provider_events';

    protected $fillable = [
        'provider_event_id',
        'type',
        'payload',       // JSON raw (biće cast u array)
        'processed_at',
        'status',        // ok|error
        'error_message', // nullable
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];
}
