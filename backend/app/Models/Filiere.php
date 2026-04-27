<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    protected $fillable = ['nom', 'code', 'description', 'active'];

    public function etudiants(): HasMany
    {
        return $this->hasMany(Etudiant::class);
    }

    public function enseignants(): HasMany
    {
        return $this->hasMany(Enseignant::class);
    }
}
