<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enregistrement extends Model
{
    use HasFactory;

    protected $fillable = [
        'formulaire_id',
        'donnees',
        'statut',
    ];

    protected $casts = [
        'donnees' => 'array',
    ];

    public function formulaire(): BelongsTo
    {
        return $this->belongsTo(Formulaire::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogExecutionRegle::class, 'enregistrement_id');
    }

    public function scopeStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeSoumis($query)
    {
        return $query->where('statut', 'soumis');
    }

    public function scopeExporte($query)
    {
        return $query->where('statut', 'exporte');
    }
}
