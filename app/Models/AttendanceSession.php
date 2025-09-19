<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance_sessions';

    protected $fillable = [
        'company_id', 'user_id', 'facility_id', 'location_id',
        'in_at', 'out_at', 'duration_sec', 'status', 'under_threshold',
        'in_meta', 'out_meta'
    ];

    protected $casts = [
        'in_at' => 'datetime',
        'out_at' => 'datetime',
        'in_meta' => 'array',
        'out_meta' => 'array',
        'under_threshold' => 'boolean',
    ];

    // Accessor for duration in seconds
    public function getDurationSecAttribute($value)
    {
        if ($value !== null) return $value;
        if ($this->in_at && $this->out_at) {
            return $this->out_at->diffInSeconds($this->in_at);
        }
        return null;
    }

    // Accessor for status
    public function getStatusAttribute($value)
    {
        if ($value) return $value;
        if ($this->out_at) return 'closed';
        if ($this->in_at && !$this->out_at) return 'open';
        return 'unknown';
    }

    // Accessor for under_threshold
    public function getUnderThresholdAttribute($value)
    {
        if ($value !== null) return (bool)$value;
        // You can add logic here to auto-calculate based on policy if needed
        return false;
    }

    // Relationships (if needed)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
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
    /**
     * Scope for filtering attendance sessions by user role.
     * Admins see all, managers see company, users see only their own.
     */
    public function scopeForUser($query, $user)
    {
        if (!$user) return $query->whereRaw('1=0');
        // Admins: see all
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }
        // Managers: see company
        if (method_exists($user, 'isManager') && $user->isManager()) {
            return $query->where('company_id', $user->company_id);
        }
        // Users: see only their own
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope for advanced filtering by table fields and date range.
     * $filters: array of field => value pairs
     * $dateType: 'in_at', 'out_at', or 'both'
     * $dateFrom, $dateTo: date range
     */
    public function scopeFilter($query, array $filters = [], $dateType = 'in_at', $dateFrom = null, $dateTo = null)
    {
        // Filter by all relevant fields
        foreach ([
            'company_id', 'user_id', 'facility_id', 'location_id',
            'status', 'under_threshold'
        ] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        // Filter by date range
        if ($dateFrom || $dateTo) {
            if ($dateType === 'both') {
                $query->where(function($q) use ($dateFrom, $dateTo) {
                    if ($dateFrom) {
                        $q->where('in_at', '>=', $dateFrom)->orWhere('out_at', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $q->where('in_at', '<=', $dateTo)->orWhere('out_at', '<=', $dateTo);
                    }
                });
            } elseif ($dateType === 'out_at') {
                if ($dateFrom) $query->where('out_at', '>=', $dateFrom);
                if ($dateTo) $query->where('out_at', '<=', $dateTo);
            } else { // default 'in_at'
                if ($dateFrom) $query->where('in_at', '>=', $dateFrom);
                if ($dateTo) $query->where('in_at', '<=', $dateTo);
            }
        }
        return $query;
    }
}
