<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->onDelete('cascade');
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enseignant_id')->nullable()->constrained('enseignants')->nullOnDelete(); // Assigné par chef
            $table->string('titre');
            $table->string('fichier_path'); // Chemin du PDF sur le serveur
            $table->string('fichier_nom_original'); // Nom original du fichier
            $table->unsignedBigInteger('fichier_taille')->nullable(); // Taille en octets
            $table->enum('statut', ['EN_ATTENTE', 'ANALYSE', 'CORRIGE', 'NOTE', 'ARCHIVE'])->default('EN_ATTENTE');
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
