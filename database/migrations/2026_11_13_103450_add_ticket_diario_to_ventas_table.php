<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->date('fecha_ticket')->nullable()->after('para_llevar');
            $table->unsignedInteger('numero_ticket')->nullable()->after('fecha_ticket');
        });

        $secuencias = [];

        DB::table('ventas')
            ->select(['id', 'negocio_id', 'created_at'])
            ->orderBy('id')
            ->each(function (object $venta) use (&$secuencias): void {
                $fecha = substr((string) $venta->created_at, 0, 10);
                $clave = $venta->negocio_id.'|'.$fecha;
                $secuencias[$clave] = ($secuencias[$clave] ?? 0) + 1;

                DB::table('ventas')->where('id', $venta->id)->update([
                    'fecha_ticket' => $fecha,
                    'numero_ticket' => $secuencias[$clave],
                ]);
            });

        Schema::table('ventas', function (Blueprint $table) {
            $table->unique(['negocio_id', 'fecha_ticket', 'numero_ticket']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['negocio_id', 'fecha_ticket', 'numero_ticket']);
            $table->dropColumn(['fecha_ticket', 'numero_ticket']);
        });
    }
};
