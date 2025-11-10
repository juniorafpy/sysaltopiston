<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Eliminando registro de migración de cobros...\n";

DB::table('migrations')
    ->where('migration', 'like', '%create_cobros_table')
    ->delete();

echo "Registro eliminado. Ahora ejecuta: php artisan migrate\n";
