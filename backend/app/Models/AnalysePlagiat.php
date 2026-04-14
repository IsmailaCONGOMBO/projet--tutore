<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysePlagiat extends Model
{
    protected $table = 'analyses_plagiat';

    protected $fillable = [
        'rapport_id', 
        'taux_global', 
        'taux_chapitre1',
        'taux_chapitre2',
        'taux_chapitre3',
        'taux_rapport_complet',
        'decision', 
        'payload_json'
    ];

    protected $casts = [
        'taux_global'          => 'float',
        'taux_chapitre1'       => 'float',
        'taux_chapitre2'       => 'float',
        'taux_chapitre3'       => 'float',
        'taux_rapport_complet' => 'float',
        'payload_json'         => 'array',
    ];

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(Rapport::class);
    }
}
