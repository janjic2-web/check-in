<?php
namespace App\Services;

use App\Models\AttendanceSession;
use Illuminate\Support\Facades\DB;

class SessionPairingService
{
    /**
     * Handle IN event: create or update attendance session.
     * Returns session_id.
     */
    public static function handleIn($companyId, $userId, $facilityId, $locationId, $inMeta = [])
    {
        return DB::transaction(function () use ($companyId, $userId, $facilityId, $locationId, $inMeta) {
            // SELECT ... FOR UPDATE
            $openSession = AttendanceSession::where([
                'company_id' => $companyId,
                'user_id' => $userId,
                'facility_id' => $facilityId,
                'location_id' => $locationId,
                'out_at' => null,
            ])->lockForUpdate()->first();

            if ($openSession) {
                // Mark orphan_in
                $openSession->status = 'orphan_in';
                $openSession->save();
            }

            // Create new session
            $session = AttendanceSession::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'facility_id' => $facilityId,
                'location_id' => $locationId,
                'in_at' => now(),
                'in_meta' => $inMeta,
                'status' => 'open',
            ]);

            return $session->id;
        });
    }

    /**
     * Handle OUT event: update attendance session or create orphan_out.
     * Returns session_id.
     */
    public static function handleOut($companyId, $userId, $facilityId, $locationId, $outMeta = [], $sessionId = null, $minInOutGapMin = 0)
    {
        return DB::transaction(function () use ($companyId, $userId, $facilityId, $locationId, $outMeta, $sessionId, $minInOutGapMin) {
            // Find open session by session_id or keys
            $query = AttendanceSession::where([
                'company_id' => $companyId,
                'user_id' => $userId,
                'facility_id' => $facilityId,
                'location_id' => $locationId,
                'out_at' => null,
            ]);
            if ($sessionId) {
                $query->where('id', $sessionId);
            }
            $session = $query->lockForUpdate()->first();

            if ($session) {
                $session->out_at = now();
                $session->out_meta = $outMeta;
                $duration = $session->in_at ? $session->out_at->diffInSeconds($session->in_at) : null;
                if ($duration !== null) {
                    if ($duration < 0 || $duration > 604800) { // 7 dana
                        $duration = 0;
                    }
                }
                $session->duration_sec = $duration;
                $session->status = 'closed';
                $session->under_threshold = $minInOutGapMin > 0 && $session->duration_sec < ($minInOutGapMin * 60);
                $session->save();
                return $session->id;
            } else {
                // Orphan OUT
                $orphan = AttendanceSession::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'facility_id' => $facilityId,
                    'location_id' => $locationId,
                    'out_at' => now(),
                    'out_meta' => $outMeta,
                    'status' => 'orphan_out',
                ]);
                return $orphan->id;
            }
        });
    }
}
