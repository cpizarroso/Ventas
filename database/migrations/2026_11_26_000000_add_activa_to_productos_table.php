<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a reversible deactivation flag so products are never destroyed.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->boolean('activa')->default(true)->after('stock');
            $table->index(['categoria_id', 'activa'], 'productos_categoria_activa_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropIndex('productos_categoria_activa_index');
            $table->dropColumn('activa');
        });
    }
};
