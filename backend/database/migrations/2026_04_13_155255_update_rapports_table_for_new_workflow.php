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
        Schema::table('rapports', function (Blueprint $table) {
            // Changement du statut vers le nouveau workflow
            $table->string('statut')->change();
            
            // Nouvelles colonnes métriques et notation
            $table->float('taux_plagiat')->nullable()->after('statut');
            $table->float('seuil_plagiat')->default(20.0)->after('taux_plagiat');
            $table->float('note')->nullable()->after('seuil_plagiat');
            $table->text('commentaire')->nullable()->after('note');
            
            // Traçabilité temporelle
            $table->timestamp('date_analyse')->nullable();
            $table->timestamp('date_correction')->nullable();
            $table->timestamp('date_validation_admin')->nullable();
            $table->timestamp('date_validation_finale')->nullable();
        });

        // Remise en enum avec les nouvelles valeurs (sans TEST car volatile)
        DB::statement("ALTER TABLE rapports MODIFY COLUMN statut ENUM(
            'EN_ATTENTE_ANALYSE_CHEF',
            'REJETE_PLAGIAT',
            'VALIDE_PLAGIAT',
            'ASSIGNE_ENSEIGNANT',
            'NOTE_SOUMISE',
            'NOTE_VALIDEE_ADMIN',
            'NOTE_REJETEE_ADMIN',
            'VALIDE_FINAL',
            'REJETE_FINAL'
        ) DEFAULT 'EN_ATTENTE_ANALYSE_CHEF'");
    }

    public function down(): void
    {
        Schema::table('rapports', function (Blueprint $table) {
            $table->dropColumn([
                'taux_plagiat', 'seuil_plagiat', 'note', 'commentaire',
                'date_analyse', 'date_correction', 'date_validation_admin', 'date_validation_finale'
            ]);
            $table->string('statut')->change();
        });

        DB::statement("ALTER TABLE rapports MODIFY COLUMN statut ENUM('EN_ATTENTE', 'ANALYSE', 'CORRIGE', 'NOTE', 'ARCHIVE') DEFAULT 'EN_ATTENTE'");
    }
};
