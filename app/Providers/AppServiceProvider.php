<?php

declare(strict_types=1);

namespace App\Providers;

use TallStackUi\Facades\TallStackUi;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        TallStackUi::customize('card', scope: 'breadcrumb')
            ->block('body')
            ->append('flex items-center justify-between');
    }
}
