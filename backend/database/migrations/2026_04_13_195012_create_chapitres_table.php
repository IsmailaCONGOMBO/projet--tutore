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
        Schema::create('chapitres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapport_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->tinyInteger('numero')->nullable();
            $table->longText('contenu_texte')->nullable();
            $table->decimal('taux_plagiat', 5, 2)->nullable();
            $table->integer('nb_mots')->default(0);
            $table->string('doc_similaire')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapitres');
    }
};
