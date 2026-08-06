<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GamevaultApiController;
use App\Http\Controllers\SystemUpdateController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('gamevault')->group(function () {
    Route::get('health', [GamevaultApiController::class, 'health']);
    Route::post('auth/register', [GamevaultApiController::class, 'register']);
    Route::post('auth/login', [GamevaultApiController::class, 'login']);

    Route::get('games', [GamevaultApiController::class, 'games']);
    Route::post('games', [GamevaultApiController::class, 'saveGame']);
    Route::delete('games/{id}', [GamevaultApiController::class, 'deleteGame']);
    Route::post('uid-check', [GamevaultApiController::class, 'checkUid']);

    Route::post('packages', [GamevaultApiController::class, 'savePackage']);
    Route::delete('packages/{id}', [GamevaultApiController::class, 'deletePackage']);

    Route::get('site-settings', [GamevaultApiController::class, 'siteSettings']);
    Route::post('site-settings', [GamevaultApiController::class, 'saveSiteSettings']);

    Route::get('users/{uid}', [GamevaultApiController::class, 'user']);
    Route::post('users', [GamevaultApiController::class, 'saveUser']);
    Route::get('admin/users', [GamevaultApiController::class, 'allUsers']);

    Route::get('transactions', [GamevaultApiController::class, 'transactions']);
    Route::post('transactions', [GamevaultApiController::class, 'saveTransaction']);

    Route::get('comments', [GamevaultApiController::class, 'comments']);
    Route::post('comments', [GamevaultApiController::class, 'saveComment']);
    Route::post('comments/{id}/approve', [GamevaultApiController::class, 'approveComment']);
    Route::delete('comments/{id}', [GamevaultApiController::class, 'deleteComment']);
});

// System update — protected by X-Update-Token header (see SYSTEM_UPDATE_SECRET in .env)
Route::post('/admin/system/update', [SystemUpdateController::class, 'runUpdate']);
