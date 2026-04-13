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
        Schema::table('themes', function (Blueprint $table) {
            // Changement du statut vers le nouveau workflow
            // Note: On change d'abord le type vers string car enum modification peut être capricieuse suivant le driver DB
            $table->string('statut')->change();
            
            // Ajout des colonnes de validation
            $table->foreignId('valide_par_chef')->nullable()->after('motif_rejet')->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par_admin')->nullable()->after('valide_par_chef')->constrained('users')->nullOnDelete();
            
            $table->timestamp('date_validation_chef')->nullable()->after('valide_le');
            $table->timestamp('date_validation_admin')->nullable()->after('date_validation_chef');
        });

        // Remise en enum avec les nouvelles valeurs
        DB::statement("ALTER TABLE themes MODIFY COLUMN statut ENUM('EN_ATTENTE_CHEF', 'VALIDE_CHEF', 'REJETE_CHEF', 'VALIDE_ADMIN', 'REJETE_ADMIN') DEFAULT 'EN_ATTENTE_CHEF'");
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropForeign(['valide_par_chef']);
            $table->dropForeign(['valide_par_admin']);
            $table->dropColumn(['valide_par_chef', 'valide_par_admin', 'date_validation_chef', 'date_validation_admin']);
            
            // Retour au statut initial
            $table->string('statut')->change();
        });

        DB::statement("ALTER TABLE themes MODIFY COLUMN statut ENUM('EN_ATTENTE', 'VALIDE', 'REJETE') DEFAULT 'EN_ATTENTE'");
    }
};
