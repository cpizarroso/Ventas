<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->foreignId('promocion_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('promociones')
                ->nullOnDelete();
            $table->boolean('es_promocion')->default(false)->after('promocion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promocion_id');
            $table->dropColumn('es_promocion');
        });
    }
};
