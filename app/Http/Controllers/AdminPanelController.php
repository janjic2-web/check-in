<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminPanelController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            abort(403, 'Access denied');
        }
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        return view('admin.dashboard', ['user' => $user]);
    }

    public function users() {
        $users = \App\Models\User::where('role', 'employee')->get();
        return view('admin.users', compact('users'));
    }

    public function resendVerification(Request $request, \App\Models\User $user) {
        if (!empty($user->email) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            // Audit log
            \Log::info('Admin resent verification email', [
                'admin_id' => $request->user()->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'resend_verification',
                'timestamp' => now(),
            ]);
        }
        return back()->with('status', 'Verification email sent.');
    }
}
