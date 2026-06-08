<?php
require 'vendor/autoload.php';

$loader = require 'vendor/autoload.php';
$file = $loader->findFile('App\\Models\\EntregaVehiculo');
echo "findFile result: " . var_export($file, true) . PHP_EOL;

if (!$file) {
    // Check PSR-4 directly
    $ref = new ReflectionClass($loader);
    $dirsProp = $ref->getProperty('prefixDirsPsr4');
    $dirsProp->setAccessible(true);
    $dirs = $dirsProp->getValue($loader);
    echo "App\\ dirs:\n";
    print_r($dirs['App\\'] ?? 'not set');
}
