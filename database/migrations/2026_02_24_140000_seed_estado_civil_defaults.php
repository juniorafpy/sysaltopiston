<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['descripcion' => 'SOLTERO/A'],
            ['descripcion' => 'CASADO/A'],
            ['descripcion' => 'DIVORCIADO/A'],
            ['descripcion' => 'VIUDO/A'],
            ['descripcion' => 'UNIÓN LIBRE'],
        ];

        foreach ($defaults as $row) {
            DB::table('estado_civil')->updateOrInsert(
                ['descripcion' => $row['descripcion']],
                ['descripcion' => $row['descripcion']]
            );
        }
    }

    public function down(): void
    {
        DB::table('estado_civil')
            ->whereIn('descripcion', [
                'SOLTERO/A',
                'CASADO/A',
                'DIVORCIADO/A',
                'VIUDO/A',
                'UNIÓN LIBRE',
            ])
            ->delete();
    }
};
