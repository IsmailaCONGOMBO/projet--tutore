<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $fillable = [
        'rapport_id', 'enseignant_id', 'valeur',
        'commentaire', 'soumise', 'soumise_le'
    ];

    protected $casts = [
        'valeur'     => 'float',
        'soumise'    => 'boolean',
        'soumise_le' => 'datetime',
    ];

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(Rapport::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }
}
