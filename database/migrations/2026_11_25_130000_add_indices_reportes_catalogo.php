<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the composite indexes used by sales reports, cash register charts,
     * the open-cash-register lookup and the ordered POS catalog.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->index(['negocio_id', 'user_id', 'created_at'], 'ventas_negocio_user_created_index');
            $table->index(['caja_id', 'created_at'], 'ventas_caja_created_index');
        });

        Schema::table('venta_detalles', function (Blueprint $table): void {
            $table->index(['venta_id', 'es_promocion'], 'venta_detalles_venta_promocion_index');
        });

        Schema::table('categorias', function (Blueprint $table): void {
            $table->index(['negocio_id', 'activa', 'orden', 'nombre'], 'categorias_negocio_activa_orden_index');
        });

        Schema::table('cajas', function (Blueprint $table): void {
            $table->index(['negocio_id', 'user_id', 'estado', 'fecha_apertura'], 'cajas_negocio_user_estado_apertura_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table): void {
            $table->dropIndex('cajas_negocio_user_estado_apertura_index');
        });

        Schema::table('categorias', function (Blueprint $table): void {
            $table->dropIndex('categorias_negocio_activa_orden_index');
        });

        // MySQL descarta el índice simple de la FK cuando el compuesto lo cubre.
        // Al revertir hay que recrearlo antes de quitar el compuesto.
        Schema::table('venta_detalles', function (Blueprint $table): void {
            $table->index('venta_id', 'venta_detalles_venta_id_foreign');
            $table->dropIndex('venta_detalles_venta_promocion_index');
        });

        Schema::table('ventas', function (Blueprint $table): void {
            $table->index('caja_id', 'ventas_caja_id_foreign');
            $table->dropIndex('ventas_caja_created_index');
            $table->dropIndex('ventas_negocio_user_created_index');
        });
    }
};
