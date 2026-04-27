<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

    protected $fillable = [
        'user_id', 'action', 'cible_type', 'cible_id', 'details', 'ip_address'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
