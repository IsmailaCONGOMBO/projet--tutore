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
            $table->decimal('taux_global', 5, 2)->default(0);
            $table->decimal('taux_chapitre1', 5, 2)->nullable();
            $table->decimal('taux_chapitre2', 5, 2)->nullable();
            $table->decimal('taux_chapitre3', 5, 2)->nullable();
            $table->decimal('taux_rapport_complet', 5, 2)->nullable();
            $table->enum('decision', ['accepte', 'rejete'])->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses_plagiat');
    }
};
