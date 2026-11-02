<?php

declare(strict_types=1);

use App\Livewire\Users;
use App\Livewire\StarterPage;
use App\Livewire\User\Profile;
use App\Livewire\PaymentMethods;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/users', Users\Index::class)->name('users.index');

    Route::get('/payment-methods', PaymentMethods\Index::class)->name('payment-methods.index');

    Route::get('/user/profile', Profile::class)->name('user.profile');

    Route::get('/starter-page', StarterPage::class)->name('starter-page');

    require __DIR__.'/inventory.php';

    require __DIR__.'/organization.php';
});
