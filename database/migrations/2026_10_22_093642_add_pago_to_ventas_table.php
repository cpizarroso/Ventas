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
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('metodo_pago', 20)->default('efectivo')->after('total');
            $table->decimal('monto_recibido', 12, 2)->nullable()->after('metodo_pago');
            $table->decimal('cambio', 12, 2)->nullable()->after('monto_recibido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'monto_recibido', 'cambio']);
        });
    }
};
