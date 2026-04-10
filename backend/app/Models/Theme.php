<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    protected $fillable = [
        'etudiant_id', 'titre', 'description', 'statut',
        'motif_rejet', 'valide_par', 'valide_le'
    ];

    protected $casts = [
        'valide_le' => 'datetime',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function rapport(): HasOne
    {
        return $this->hasOne(Rapport::class);
    }
}
