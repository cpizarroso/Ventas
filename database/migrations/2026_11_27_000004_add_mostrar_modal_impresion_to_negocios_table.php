<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow the business to suppress the receipt prompt after a sale.
     */
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->boolean('mostrar_modal_impresion')
                ->default(true)
                ->after('imprimir_ticket_predeterminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('mostrar_modal_impresion');
        });
    }
};
