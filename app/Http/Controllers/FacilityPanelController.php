<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class FacilityPanelController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user->isFacilityAdmin()) {
            abort(403, 'Access denied');
        }
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        return view('facility.dashboard', ['user' => $user]);
    }
}
