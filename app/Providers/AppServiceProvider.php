<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\Debugbar\Facades\Debugbar; // Tambahkan ini

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
        // Set lokalisasi bahasa Indonesia
        Carbon::setLocale('id');
        
        // Format tanggal khusus untuk Indonesia
        config(['app.locale' => 'id']);
        
        // Aktifkan Debugbar jika di environment local
        if (app()->environment('local') && class_exists(Debugbar::class)) {
            Debugbar::enable();
        }
        
        // Monitoring query untuk development
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                Log::channel('daily')->info(
                    'SQL: ' . $query->sql,
                    [
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms'
                    ]
                );
            });
        }
    }
}