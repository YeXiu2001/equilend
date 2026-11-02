<?php

declare(strict_types=1);

use App\Livewire\Organization\Index;
use Illuminate\Support\Facades\Route;
use App\Livewire\Organization\Settings;

Route::prefix('/organization')->group(function () {
    Route::get('/', Index::class)->name('organization.index');
    Route::get('/settings', Settings::class)->name('organization.settings');
});
