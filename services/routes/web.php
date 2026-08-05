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