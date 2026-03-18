<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (! is_array($error)) {
        return;
    }

    $logPath = __DIR__.'/storage/logs/laravel.log';
    $message = '['.date('Y-m-d H:i:s').'] bootstrap.ERROR: '.$error['message'].' in '.$error['file'].':'.$error['line'].' uri='.($_SERVER['REQUEST_URI'] ?? '/')."\n";
    @file_put_contents($logPath, $message, FILE_APPEND);
});

try {
    require __DIR__.'/vendor/autoload.php';

    $app = require_once __DIR__.'/bootstrap/app.php';

    $kernel = $app->make(Kernel::class);

    $response = $kernel->handle(
        $request = Request::capture()
    )->send();

    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    $logPath = __DIR__.'/storage/logs/laravel.log';
    $message = '['.date('Y-m-d H:i:s').'] bootstrap.EXCEPTION: '.$e::class.' '.$e->getMessage().' uri='.($_SERVER['REQUEST_URI'] ?? '/')."\n".$e->getTraceAsString()."\n";
    @file_put_contents($logPath, $message, FILE_APPEND);

    throw $e;
}
