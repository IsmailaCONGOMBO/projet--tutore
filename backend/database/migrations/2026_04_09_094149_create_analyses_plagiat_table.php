<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analyses_plagiat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapport_id')->constrained()->onDelete('cascade');
            $table->decimal('taux_plagiat', 5, 2)->default(0); // Pourcentage ex: 23.45
            $table->json('passages_suspects')->nullable(); // Liste des passages détectés
            $table->enum('statut', ['EN_COURS', 'TERMINE', 'ECHEC'])->default('EN_COURS');
            $table->timestamp('analyse_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses_plagiat');
    }
};
