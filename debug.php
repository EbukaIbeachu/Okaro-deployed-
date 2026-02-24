<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Diagnostic Tool</h1>";

echo "<h2>1. PHP Environment</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";

echo "<h2>2. Critical Files Check</h2>";
$critical_files = [
    'vendor/autoload.php',
    'bootstrap/app.php',
    '.env',
    '.htaccess'
];

foreach ($critical_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<span style='color:green'>[OK]</span> $file exists.<br>";
    } else {
        echo "<span style='color:red'>[MISSING]</span> $file is missing!<br>";
    }
}

echo "<h2>3. Permissions Check</h2>";
$dirs = [
    'storage',
    'storage/logs',
    'storage/framework',
    'storage/framework/views',
    'bootstrap/cache'
];

foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "<span style='color:green'>[WRITABLE]</span> $dir is writable.<br>";
        } else {
            echo "<span style='color:orange'>[NOT WRITABLE]</span> $dir exists but is not writable (might be normal on some setups if user matches).<br>";
        }
    } else {
        echo "<span style='color:red'>[MISSING]</span> $dir directory does not exist.<br>";
    }
}

echo "<h2>4. Laravel Autoloader Test</h2>";
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
        echo "<span style='color:green'>[SUCCESS]</span> Vendor autoloader loaded.<br>";
        
        echo "<h2>5. Laravel App Boot Test</h2>";
        $app = require_once __DIR__.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        echo "<span style='color:green'>[SUCCESS]</span> Laravel App instance created.<br>";
        
        echo "<h2>6. Database Connection Test (from .env)</h2>";
        try {
            // Very basic check if we can resolve DB config
            $config = $app['config']['database.connections.mysql'];
            echo "DB Host: " . ($config['host'] ?? 'Not set') . "<br>";
            echo "DB Database: " . ($config['database'] ?? 'Not set') . "<br>";
            echo "DB Username: " . ($config['username'] ?? 'Not set') . "<br>";
        } catch (\Throwable $e) {
             echo "Could not read DB config: " . $e->getMessage() . "<br>";
        }

    } else {
        echo "<span style='color:red'>[FAIL]</span> Cannot test Laravel because autoloader is missing.<br>";
    }
} catch (\Throwable $e) {
    echo "<span style='color:red'>[CRITICAL ERROR]</span> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
