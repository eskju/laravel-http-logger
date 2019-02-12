<?php

namespace Eskju\HttpLogger;

use Illuminate\Support\ServiceProvider;

class HttpLoggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!defined('REQUEST_ID')) {
            define('REQUEST_ID', uniqid());
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/http-logger.php' => config_path('http-logger.php'),
            ], 'config');
        }

        $this->app->singleton(LogProfile::class, config('http-logger.log_profile'));
        $this->app->singleton(LogWriter::class, config('http-logger.log_writer'));

        try {
            $this->app['db']->listen(
                function ($query, $bindings = null, $time = null, $connectionName = null) {
                    $log = $this->app->make(LogWriter::class);
                    $log->addQuery($query);
                }
            );
        } catch (\Exception $e) {
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/http-logger.php', 'http-logger');
    }
}
