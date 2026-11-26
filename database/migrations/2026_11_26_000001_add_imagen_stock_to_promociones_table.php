<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the optional image and the optional promotion stock.
     */
    public function up(): void
    {
        Schema::table('promociones', function (Blueprint $table) {
            $table->string('imagen', 255)->nullable()->after('descripcion');
            $table->integer('stock')->nullable()->after('precio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promociones', function (Blueprint $table) {
            $table->dropColumn(['imagen', 'stock']);
        });
    }
};
