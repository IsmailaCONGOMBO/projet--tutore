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

    // Accessors pour compatibilité avec l'ancien système / frontend
    public function getTauxPlagiatAttribute()
    {
        return $this->taux_global;
    }

    public function getStatutAttribute()
    {
        return $this->decision === 'accepte' ? 'VALIDE' : ($this->decision === 'rejete' ? 'REJETE' : 'EN_COURS');
    }

    public function getAnalyseLeAttribute()
    {
        return $this->created_at;
    }

    public function getPassagesSuspectsAttribute()
    {
        $payload = $this->payload_json;
        if (isset($payload['passages_suspects'])) return $payload['passages_suspects'];
        if (isset($payload['segments'])) return $payload['segments']; // compatibilité possible
        return [];
    }
}
