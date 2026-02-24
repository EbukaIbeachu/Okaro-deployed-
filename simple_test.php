<?php
// Pure PHP Test - No Laravel Dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Server Status: ONLINE</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>If you can see this, PHP is working correctly.</p>";
echo "<p>Current Directory: " . __DIR__ . "</p>";

// Check for vendor folder presence only (do not load it)
$vendor_exists = is_dir(__DIR__ . '/vendor') ? 'Yes' : 'No';
echo "<p>Vendor folder exists: $vendor_exists</p>";
