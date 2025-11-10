<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estado_civil', function (Blueprint $table) {
            $table->id('cod_estado_civil');
            $table->string('descripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estado_civil');
    }
};
