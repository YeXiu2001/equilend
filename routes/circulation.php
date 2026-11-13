<?php

declare(strict_types=1);

use App\Livewire\Circulation\Desk;
use Illuminate\Support\Facades\Route;
use App\Livewire\Circulation\ActiveLoans;
use App\Livewire\Circulation\BookingRequests;
use App\Livewire\Circulation\OverdueTracking;
use App\Livewire\Circulation\AvailabilityCalendar;

Route::get('/circulation/desk', Desk::class)->name('circulation.desk');
Route::get('/circulation/loans', ActiveLoans::class)->name('circulation.loans');
Route::get('/circulation/reservations', BookingRequests::class)->name('circulation.reservations');
Route::get('/circulation/overdue', OverdueTracking::class)->name('circulation.overdue');
Route::get('/circulation/calendar', AvailabilityCalendar::class)->name('circulation.calendar');
