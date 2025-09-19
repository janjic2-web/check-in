<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationTag extends Model
{
    use HasFactory;

    protected $table = 'location_tags';

    protected $fillable = [
        'company_id',
        'location_id',
        'tag_uid',
    ];

    protected $casts = [
        'company_id'  => 'integer',
        'location_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    // Relacije
    public function company()  { return $this->belongsTo(Company::class); }
    public function location() { return $this->belongsTo(Location::class); }
}
