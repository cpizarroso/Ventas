<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Default choice for the optional receipt prompt after a sale.
     */
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->boolean('imprimir_ticket_predeterminado')
                ->default(false)
                ->after('metodo_pago_predeterminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('imprimir_ticket_predeterminado');
        });
    }
};
