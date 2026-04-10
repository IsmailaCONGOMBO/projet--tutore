<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('filiere_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule')->unique()->nullable();
            $table->string('niveau')->nullable(); // L3, M1, M2...
            $table->string('annee_academique')->nullable(); // 2025-2026
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};
