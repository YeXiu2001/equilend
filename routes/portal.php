<?php

declare(strict_types=1);

use App\Livewire\Borrower;
use Illuminate\Support\Facades\Route;

Route::get('/portal/browse', Borrower\BrowseEquipment::class)->name('portal.browse');
Route::get('/portal/reservations', Borrower\Reservations::class)->name('portal.reservations');
Route::get('/portal/loans', Borrower\ActiveLoans::class)->name('portal.loans');
Route::get('/portal/history', Borrower\History::class)->name('portal.history');
Route::get('/portal/penalties', Borrower\Penalties::class)->name('portal.penalties');
