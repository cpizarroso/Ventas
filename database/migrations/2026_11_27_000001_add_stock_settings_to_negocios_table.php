<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business-wide stock defaults and the policy for overselling.
     */
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->unsignedInteger('stock_min')
                ->default(5)
                ->after('imprimir_ticket_predeterminado');
            $table->boolean('permitir_stock_negativo')
                ->default(false)
                ->after('stock_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['stock_min', 'permitir_stock_negativo']);
        });
    }
};
