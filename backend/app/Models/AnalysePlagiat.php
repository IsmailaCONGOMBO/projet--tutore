<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysePlagiat extends Model
{
    protected $table = 'analyses_plagiat';

    protected $fillable = [
        'rapport_id', 'taux_plagiat', 'passages_suspects',
        'statut', 'analyse_le'
    ];

    protected $casts = [
        'taux_plagiat'      => 'float',
        'passages_suspects' => 'array',
        'analyse_le'        => 'datetime',
    ];

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(Rapport::class);
    }
}
