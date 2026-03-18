<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (! is_array($error)) {
        return;
    }

    $logPath = __DIR__.'/../storage/logs/laravel.log';
    $message = '['.date('Y-m-d H:i:s').'] bootstrap.ERROR: '.$error['message'].' in '.$error['file'].':'.$error['line'].' uri='.($_SERVER['REQUEST_URI'] ?? '/')."\n";
    @file_put_contents($logPath, $message, FILE_APPEND);
});

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

try {
    require __DIR__.'/../vendor/autoload.php';

    /*
    |--------------------------------------------------------------------------
    | Run The Application
    |--------------------------------------------------------------------------
    |
    | Once we have the application, we can handle the incoming request using
    | the application's HTTP kernel. Then, we will send the response back
    | to this client's browser, allowing them to enjoy our creative
    | and wonderful application.
    |
    */

    $app = require_once __DIR__.'/../bootstrap/app.php';

    $kernel = $app->make(Kernel::class);

    $response = $kernel->handle(
        $request = Request::capture()
    )->send();

    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    $logPath = __DIR__.'/../storage/logs/laravel.log';
    $message = '['.date('Y-m-d H:i:s').'] bootstrap.EXCEPTION: '.$e::class.' '.$e->getMessage().' uri='.($_SERVER['REQUEST_URI'] ?? '/')."\n".$e->getTraceAsString()."\n";
    @file_put_contents($logPath, $message, FILE_APPEND);

    throw $e;
}
