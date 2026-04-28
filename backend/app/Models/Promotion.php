<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = ['annee', 'libelle'];

    public function etudiants(): HasMany
    {
        return $this->hasMany(Etudiant::class);
    }
}
