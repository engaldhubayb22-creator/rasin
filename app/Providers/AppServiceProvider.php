<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // طول افتراضي للفهارس متوافق مع إصدارات MySQL القديمة
        Schema::defaultStringLength(191);
    }
}
