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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cedula', 30)->nullable()->after('name');
            $table->string('telefono', 30)->nullable()->after('cedula');
            $table->string('direccion', 255)->nullable()->after('telefono');
            $table->string('email')->nullable()->change();
            $table->unique(['negocio_id', 'cedula']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['negocio_id', 'cedula']);
            $table->dropColumn(['cedula', 'telefono', 'direccion']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
