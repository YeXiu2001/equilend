<?php

declare(strict_types=1);

use App\Livewire\Users\Index;
use App\Livewire\User\Profile;
use Illuminate\Support\Facades\Route;
use App\Livewire\StarterPage;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/users', Index::class)->name('users.index');

    Route::get('/user/profile', Profile::class)->name('user.profile');

    Route::get('/starter-page', StarterPage::class)->name('starter-page');
});
