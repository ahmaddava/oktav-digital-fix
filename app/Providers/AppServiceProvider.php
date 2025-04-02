<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

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
    public function boot()
    {
        // Set lokalisasi bahasa Indonesia
        Carbon::setLocale('id');
        
        // Format tanggal khusus untuk Indonesia
        config(['app.locale' => 'id']);
    }
}
