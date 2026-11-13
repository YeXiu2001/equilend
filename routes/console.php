<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Console\ClosureCommand;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('equilend:check-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

Schedule::command('equilend:check-overdue')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);
