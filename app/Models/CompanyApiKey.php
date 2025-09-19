<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'company_api_keys';

    protected $fillable = [
        'company_id','key','active','is_superadmin','last_used_at'
    ];

    protected $casts = [
        'active'        => 'boolean',
        'is_superadmin' => 'boolean',
        'last_used_at'  => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
