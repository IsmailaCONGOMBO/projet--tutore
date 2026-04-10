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
        'fichier_taille', 'statut', 'archive'
    ];

    protected $casts = [
        'archive' => 'boolean',
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
