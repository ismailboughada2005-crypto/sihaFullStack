<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    // غايدير migrate و seed بوحدو ف السيرفر بلا ما يحتاج Shell
    if (config('app.env') === 'production') {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        } catch (\Exception $e) {
            // كايتجاهل الغلط إذا كانوا الجداول ديجا ناضيين
        }
    }
}


}
