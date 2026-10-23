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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->boolean('activa')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            // Unique per business. Comparación de texto respetada por la política
            // de collation de la base de datos (utf8mb4_unicode_ci en MySQL,
            // case-sensitive por defecto en SQLite; la validación de negocio
            // añade además comprobación case-insensitive en la capa de aplicación).
            $table->unique(['negocio_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
