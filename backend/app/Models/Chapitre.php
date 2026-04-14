<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapitre extends Model
{
    protected $fillable = [
        'rapport_id',
        'label',
        'numero',
        'contenu_texte',
        'taux_plagiat',
        'nb_mots',
        'doc_similaire'
    ];

    protected $casts = [
        'taux_plagiat' => 'float',
        'nb_mots' => 'integer',
        'numero' => 'integer',
    ];

    /**
     * Relation avec le rapport.
     */
    public function rapport(): BelongsTo
    {
        return $this->belongsTo(Rapport::class);
    }
}
