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
        Schema::table('notes', function (Blueprint $table) {
            $table->enum('statut_validation', ['EN ATTENTE', 'VALIDÉ', 'REJETÉ'])->default('EN ATTENTE');
            $table->text('motif_rejet')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['statut_validation', 'motif_rejet']);
        });
    }
};
