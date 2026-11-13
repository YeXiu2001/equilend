<?php

declare(strict_types=1);

use App\Livewire\Payments;
use App\Livewire\Penalties;
use Illuminate\Support\Facades\Route;

Route::get('/finance/penalties', Penalties\Index::class)->name('finance.penalties');
Route::get('/finance/payments', Payments\Index::class)->name('finance.payments');
