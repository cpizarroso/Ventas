<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enable overselling by default, including businesses created before the
     * setting existed (which kept the old false default).
     */
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table): void {
            $table->boolean('permitir_stock_negativo')
                ->default(true)
                ->change();
        });

        DB::table('negocios')
            ->where('permitir_stock_negativo', false)
            ->update(['permitir_stock_negativo' => true]);
    }

    /**
     * Restore the previous default and value for businesses using the new one.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table): void {
            $table->boolean('permitir_stock_negativo')
                ->default(false)
                ->change();
        });

        DB::table('negocios')
            ->where('permitir_stock_negativo', true)
            ->update(['permitir_stock_negativo' => false]);
    }
};
