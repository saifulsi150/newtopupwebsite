<?php

/**
 * web.php — API-only routes
 *
 * All HTML pages are served by the Nuxt frontend.
 * Laravel handles only: API (routes/api.php + tastnow_api.php),
 * health check, cache clear, and PWA manifest.
 */

use App\Http\Controllers\PWAController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/healthz', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toDateTimeString()]);
});

// Artisan helpers (protected by obscurity — move behind auth if needed)
Route::get('/clear', function () {
    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    Artisan::call('optimize:clear', [], $output);
    return $output->fetch();
})->name('/clear');

Route::get('schedule-run', function () {
    return Artisan::call('schedule:run');
})->name('cron');

// PWA manifest and offline page
Route::get('/manifest.json', [PWAController::class, 'manifestJson'])->name('manifest');
Route::get('/offline.html', [PWAController::class, 'offline']);

// Standard form POST handler for Filament Admin Login
Route::post('/admin/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $remember = $request->boolean('remember');

    if (\Illuminate\Support\Facades\Auth::guard('admin')->attempt($credentials, $remember)) {
        $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
        if ($admin && ! $admin->is_active) {
            \Illuminate\Support\Facades\Auth::guard('admin')->logout();
            return back()->withErrors(['email' => 'Your account is disabled.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        return redirect()->intended('/admin');
    }

    return back()->withErrors([
        'email' => 'These credentials do not match our records.',
    ])->withInput($request->only('email'));
})->middleware(['web'])->name('filament.admin.auth.login.post');

