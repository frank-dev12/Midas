<?php

use App\Http\Controllers\TenantSelectController;
use App\Http\Middleware\EnsurePosAccess;
use App\Http\Middleware\EnsureTenantSelected;
use App\Livewire\Pos\PosPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('/tenant/select', [TenantSelectController::class, 'show'])->name('tenant.select');
    Route::post('/tenant/select', [TenantSelectController::class, 'store'])->name('tenant.select.store');
});

Route::middleware(['auth', 'verified', EnsureTenantSelected::class])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    Route::get('/pos', PosPage::class)
        ->middleware([EnsurePosAccess::class])
        ->name('pos');
});

require __DIR__.'/auth.php';
