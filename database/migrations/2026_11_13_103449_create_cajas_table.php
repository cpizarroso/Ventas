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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cerrada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 10)->default('abierta');
            $table->decimal('monto_inicial', 12, 2)->default(0);
            $table->string('observaciones_apertura', 255)->nullable();
            $table->timestamp('fecha_apertura');
            $table->decimal('total_ventas', 12, 2)->default(0);
            $table->decimal('total_efectivo', 12, 2)->default(0);
            $table->decimal('total_qr', 12, 2)->default(0);
            $table->unsignedInteger('cantidad_ventas')->default(0);
            $table->decimal('monto_cierre', 12, 2)->nullable();
            $table->decimal('monto_esperado_efectivo', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->string('observaciones_cierre', 255)->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->timestamps();

            $table->index(['negocio_id', 'estado']);
            $table->index(['user_id', 'estado']);
            $table->index('fecha_apertura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
