<?php
// Force disable output buffering to see output immediately
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(1);

echo "<h1>Step-by-Step Debug</h1>";
echo "Step 1: Script started.<br>";
flush();

$vendor = __DIR__ . '/vendor';
echo "Step 2: Checking vendor folder: $vendor<br>";
if (is_dir($vendor)) {
    echo "Step 2 Result: Vendor directory exists.<br>";
} else {
    echo "Step 2 Result: Vendor directory MISSING.<br>";
    exit("STOP: No vendor folder.");
}
flush();

$autoload = $vendor . '/autoload.php';
echo "Step 3: Checking autoload.php: $autoload<br>";
if (file_exists($autoload)) {
    echo "Step 3 Result: autoload.php exists (Size: " . filesize($autoload) . " bytes).<br>";
} else {
    echo "Step 3 Result: autoload.php MISSING.<br>";
    exit("STOP: No autoload.php.");
}
flush();

echo "Step 4: Attempting to require autoload.php... (If it stops here, vendor is corrupt)<br>";
flush();

try {
    require $autoload;
    echo "Step 4 Result: Autoloader required successfully.<br>";
} catch (Throwable $e) {
    echo "Step 4 Result: Exception during autoload: " . $e->getMessage() . "<br>";
}
flush();

echo "Step 5: Checking bootstrap/app.php...<br>";
flush();

$bootstrap = __DIR__ . '/bootstrap/app.php';
if (file_exists($bootstrap)) {
    echo "Step 5 Result: bootstrap/app.php exists.<br>";
    
    echo "Step 6: Attempting to boot app...<br>";
    flush();
    
    try {
        $app = require_once $bootstrap;
        echo "Step 6 Result: App require successful.<br>";
        
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        echo "Step 7 Result: Kernel made. Laravel is BOOTED.<br>";
    } catch (Throwable $e) {
        echo "Step 6/7 Result: Boot FAILED: " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "Step 5 Result: bootstrap/app.php MISSING.<br>";
}
flush();

echo "<h2>Done.</h2>";
