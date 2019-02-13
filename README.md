# laravel-http-logger
Logger for your Laravel routes

This package is based on https://github.com/spatie/laravel-http-logger
Thanks for your great work and awesome packages!

Differences:
- Handled as After-Middleware
- Default-Support for Remote-Logging (via .env)
- Added Treshold Option
- File-Logger Fallback
- Provide a JSON with a lot of information
- Logs SQL queries
- Generates unique request IDs

## Usage

```
composer require eskju/laravel-http-logger
```

## Publish Config
```
php artisan vendor:publish --provider="Eskju\HttpLogger\HttpLoggerServiceProvider" --tag="config"
```

## Usage

Include the Middleware in your Kernel.php

```
protected $middleware = [
    // ...
    
    \Eskju\HttpLogger\Middlewares\HttpLogger::class
];
```
