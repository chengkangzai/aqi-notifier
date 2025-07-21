<?php

use App\Livewire\AqiDashboard;
use App\Livewire\AqiSettingsPanel;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\WhatsAppSessionManager;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', AqiDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Settings routes
    Route::redirect('settings', 'settings/profile');
    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // AQI Management routes
    Route::get('aqi/settings', AqiSettingsPanel::class)
        ->middleware(['auth', 'verified'])
        ->name('aqi.settings');
    Route::get('whatsapp/session', WhatsAppSessionManager::class)
        ->middleware(['auth', 'verified'])
        ->name('whatsapp.session');
});

Route::fallback(function () {
    return redirect()->route('dashboard')->with('error', 'Page not found.');
});
require __DIR__ . '/auth.php';
