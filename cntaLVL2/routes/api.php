<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RedirectAuthController;


/*
|--------------------------------------------------------------------------
| Redirect-Based API Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Main redirect authentication endpoint
    Route::get('/redirect', [RedirectAuthController::class, 'handleRedirect'])
        ->name('api.auth.redirect');
    
    // Validate credentials (for external systems)
    Route::post('/validate', [RedirectAuthController::class, 'validateCredentials'])
        ->name('api.auth.validate');
    
    // Generate auth URL (for external systems)
    Route::get('/generate-url', [RedirectAuthController::class, 'generateAuthUrl'])
        ->name('api.auth.generate-url');
});

// Your existing protected routes...
Route::middleware(['api.authenticated', 'organization.context'])->group(function () {
    
});