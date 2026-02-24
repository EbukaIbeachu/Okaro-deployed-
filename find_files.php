<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$root = __DIR__;
$names = ['php.ini', '.user.ini', 'web.config'];
$found = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    $n = strtolower($file->getFilename());
    if (in_array($n, $names)) {
        $found[] = $file->getPathname();
    }
}
header('Content-Type: text/plain; charset=UTF-8');
echo "Found: " . count($found) . "\n";
foreach ($found as $p) {
    echo $p . "\n";
}
if (isset($_GET['delete']) && $_GET['delete'] === '1') {
    $deleted = [];
    $failed = [];
    foreach ($found as $p) {
        if (@unlink($p)) {
            $deleted[] = $p;
        } else {
            $failed[] = $p;
        }
    }
    echo "\nDeleted: " . count($deleted) . "\n";
    foreach ($deleted as $p) {
        echo $p . "\n";
    }
    echo "\nFailed: " . count($failed) . "\n";
    foreach ($failed as $p) {
        echo $p . "\n";
    }
}
