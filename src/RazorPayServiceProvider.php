<?php

namespace Xoxoday\Razorpay;

use Illuminate\Support\ServiceProvider;

class RazorPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        if ($this->app->runningInConsole()) {
            // Publish assets
            $this->publishes([
                __DIR__ . '/config/xorazorpay.php' => config_path('xorazorpay.php'),
                __DIR__ . '/Jobs/RazorPayout.php' => app_path('Jobs\RazorPayout.php')
            ], 'razorpay_files');
        }
    }
}
