<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('categoria', 80)->index();
            $table->string('titulo', 160);
            $table->text('descripcion');
            $table->decimal('precio_desde', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // MySQL 8.x soporta FULLTEXT en InnoDB
            $table->fullText(['titulo', 'descripcion']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('services');
    }
};
