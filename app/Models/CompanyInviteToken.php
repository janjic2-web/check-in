<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CompanyInviteToken extends Model
{
    use SoftDeletes;

    protected $table = 'company_invite_tokens';
    protected $fillable = [
        'token', 'expires_at', 'used_at', 'audit_log'
    ];
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'audit_log' => 'array',
    ];

    public function isValid()
    {
        return !$this->used_at && $this->expires_at && $this->expires_at->isFuture();
    }

    public function markUsed($userId = null)
    {
        $this->used_at = now();
        $this->audit_log = array_merge($this->audit_log ?? [], [
            ['event' => 'used', 'user_id' => $userId, 'timestamp' => now()->toDateTimeString()]
        ]);
        $this->save();
    }

    public function logInvalidAttempt($reason, $userId = null)
    {
        $this->audit_log = array_merge($this->audit_log ?? [], [
            ['event' => 'invalid_attempt', 'reason' => $reason, 'user_id' => $userId, 'timestamp' => now()->toDateTimeString()]
        ]);
        $this->save();
    }
}
