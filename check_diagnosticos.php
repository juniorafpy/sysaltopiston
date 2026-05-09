<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$recepciones = App\Models\RecepcionVehiculo::withCount('diagnosticos')->limit(10)->get();
foreach ($recepciones as $r) {
    echo 'ID: ' . $r->id . ' | diagnosticos_count: ' . $r->diagnosticos_count . PHP_EOL;
}
