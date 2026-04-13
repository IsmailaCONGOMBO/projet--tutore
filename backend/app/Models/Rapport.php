<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rapport extends Model
{
    protected $fillable = [
        'etudiant_id', 'theme_id', 'enseignant_id',
        'titre', 'fichier_path', 'fichier_nom_original',
        'fichier_taille', 'statut', 'taux_plagiat', 'seuil_plagiat',
        'note', 'commentaire', 'date_analyse', 'date_correction',
        'date_validation_admin', 'date_validation_finale', 'archive'
    ];

    protected $casts = [
        'archive' => 'boolean',
        'taux_plagiat' => 'float',
        'seuil_plagiat' => 'float',
        'note' => 'float',
        'date_analyse' => 'datetime',
        'date_correction' => 'datetime',
        'date_validation_admin' => 'datetime',
        'date_validation_finale' => 'datetime',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function note(): HasOne
    {
        return $this->hasOne(Note::class);
    }

    public function analyse(): HasOne
    {
        return $this->hasOne(AnalysePlagiat::class);
    }
}
