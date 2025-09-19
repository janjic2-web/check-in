// Signup za company admina (samo za guest, mora validan token)
use Illuminate\Http\Request;
Route::middleware(['guest'])->group(function () {
    use App\Models\CompanyInviteToken;
    Route::get('/signup/company-admin', function (Request $request) {
        $tokenValue = $request->query('token');
        $token = $tokenValue ? CompanyInviteToken::where('token', $tokenValue)->first() : null;
        if (!$token || !$token->isValid()) {
            if ($token) $token->logInvalidAttempt('invalid_or_expired', null);
            abort(403, 'Invite token je nevažeći, istekao ili iskorišćen.');
        }
        return view('auth.signup-company-admin', ['token' => $tokenValue]);
    })->name('signup.company_admin');

    use App\Http\Requests\Auth\SignupCompanyAdminRequest;
    use Illuminate\Support\Facades\Hash;
    use App\Models\User;
    use App\Models\Company;
    Route::post('/signup/company-admin', function (SignupCompanyAdminRequest $request) {
        $data = $request->validated();
        $tokenValue = $data['token'] ?? null;
        $token = $tokenValue ? CompanyInviteToken::where('token', $tokenValue)->first() : null;
        if (!$token || !$token->isValid()) {
            if ($token) $token->logInvalidAttempt('invalid_or_expired', null);
            abort(403, 'Invite token je nevažeći, istekao ili iskorišćen.');
        }

        // Kreiraj kompaniju sa default policy vrednostima
        $company = Company::create([
            'display_name' => $data['company_display_name'],
            'status' => Company::STATUS_ACTIVE,
            'allow_outside' => false,
            'default_radius_m' => 50,
            'anti_spam_min_interval' => 5,
            'offline_retention_hours' => 24,
            'min_inout_gap_min' => 10,
            'ble_min_rssi' => -70,
            'require_gps_checkin' => true,
        ]);

        // Kreiraj admin korisnika
        $user = User::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'company_id' => $company->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Označi token kao iskorišćen
        $token->markUsed($user->id);

        // Postavi company_id i signup_completed u session
        session(['company_id' => $company->id, 'signup_completed' => true]);

        // Pošalji verification email
        $user->sendEmailVerificationNotification();

        return redirect()->route('admin.company.setup');
    });
});

// Helper funkcija za validaciju tokena (dummy, implementiraj po potrebi)
// isValidInviteToken više nije potreban, koristi CompanyInviteToken model

// Zaštićena ruta za company setup panel
Route::middleware(['web', 'auth', 'verified', 'company.web', 'company.active', 'can:admin'])->group(function () {
    use App\Http\Requests\Company\CompanySetupRequest;
    Route::get('/admin/company/setup', function () {
        $company = auth()->user()->company;
        if (!session('company_id')) {
            abort(500, 'NO_COMPANY');
        }
        if (in_array($company->status, ['suspended', 'expired'])) {
            abort(403, 'FORBIDDEN');
        }
        return view('admin.company-setup', compact('company'));
    })->name('admin.company.setup');

    Route::post('/admin/company/setup', function (CompanySetupRequest $request) {
        $company = auth()->user()->company;
        if (!session('company_id')) {
            abort(500, 'NO_COMPANY');
        }
        if (in_array($company->status, ['suspended', 'expired'])) {
            abort(403, 'FORBIDDEN');
        }
        $data = $request->validated();
        $company->fill($data);
        $company->save();
        if ($request->input('action') === 'continue') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('admin.company.setup')->with('success', 'Podešavanja su sačuvana.');
    });
});
// Admin panel grupe (samo za admin)
Route::middleware(['web', 'auth', 'verified', 'company.web', 'company.active', 'can:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

// Facility panel grupe (samo za facility_admin)
Route::middleware(['web', 'auth', 'verified', 'company.web', 'company.active', 'can:facility_admin'])->prefix('facility')->group(function () {
    Route::get('/dashboard', function () {
        return view('facility.dashboard');
    })->name('facility.dashboard');
});
// Admin users list & resend verification
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/users', [\App\Http\Controllers\AdminPanelController::class, 'users'])->name('admin.users');
    Route::post('/admin/users/{user}/resend', [\App\Http\Controllers\AdminPanelController::class, 'resendVerification'])
        ->middleware('throttle:3,15')
        ->name('admin.users.resend');
});

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin panel (samo za admin, mora verified, company.web, company.active)
Route::middleware(['auth', 'verified', 'company.web', 'company.active'])->group(function () {
    Route::get('/admin', [\App\Http\Controllers\AdminPanelController::class, 'index'])->name('admin.dashboard');
});

// Facility panel (samo za facility_admin, mora verified, company.web, company.active)
Route::middleware(['auth', 'verified', 'company.web', 'company.active'])->group(function () {
    Route::get('/facility', [\App\Http\Controllers\FacilityPanelController::class, 'index'])->name('facility.dashboard');
});

require __DIR__.'/auth.php';
