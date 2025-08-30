<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('barrio', 120)->index();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lon', 9, 6)->nullable();
            $table->decimal('rating_promedio', 2, 1)->default(4.5);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('providers');
    }
};
