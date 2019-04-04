<?php

namespace mtolhuijs\LDS;

use Illuminate\Support\ServiceProvider;

class DatabaseSynchronizerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/database-synchronizer.php', 'database-synchronizer');
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        $this->commands([
            Commands\Synchronise::class,
        ]);
    }
}
