<?php

declare(strict_types=1);

use App\Livewire\Reports;
use Illuminate\Support\Facades\Route;

Route::get('/reports/inventory', Reports\InventoryReport::class)->name('reports.inventory');
Route::get('/reports/circulation', Reports\CirculationReport::class)->name('reports.circulation');
Route::get('/reports/collections', Reports\CollectionReport::class)->name('reports.collections');
