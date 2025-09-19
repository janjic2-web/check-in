<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (samo za web; API je u routes/api.php)
|--------------------------------------------------------------------------
*/

// Home (welcome)
Route::get('/', fn () => view('welcome'))->name('home');

// Dashboard (samo za prijavljene i verifikovane)
Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Profil (samo za prijavljene)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Auth rute (login, register, ...)
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| SPA catch-all (opciono)
|--------------------------------------------------------------------------
| Sve nepoznate WEB rute šalji na welcome, ali nikad /api/*
*/
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api/).*$');
