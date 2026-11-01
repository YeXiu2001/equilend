<?php

declare(strict_types=1);

use App\Livewire\Organization\Index;
use Illuminate\Support\Facades\Route;

Route::prefix('/organization')->group(function () {
    Route::get('/', Index::class)->name('organization.index');
});
