<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    protected $fillable = [
        'etudiant_id', 'titre', 'description', 'statut',
        'motif_rejet', 'valide_par_chef', 'valide_par_admin', 
        'date_validation_chef', 'date_validation_admin'
    ];

    protected $casts = [
        'date_validation_chef' => 'datetime',
        'date_validation_admin' => 'datetime',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function chef(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_chef');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_admin');
    }

    public function rapport(): HasOne
    {
        return $this->hasOne(Rapport::class);
    }
}
