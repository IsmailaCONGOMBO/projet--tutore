<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enseignant extends Model
{
    protected $fillable = [
        'user_id', 'filiere_id', 'grade', 'specialite'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function rapportsAssignes(): HasMany
    {
        return $this->hasMany(Rapport::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
