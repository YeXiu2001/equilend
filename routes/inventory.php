<?php

declare(strict_types=1);

use App\Livewire\Inventory\Index;
use Illuminate\Support\Facades\Route;

Route::get('/inventory', Index::class)->name('inventory.index');
