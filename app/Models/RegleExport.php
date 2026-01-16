<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegleExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'formulaire_id',
        'nom_regle',
        'description',
        'priorite',
        'conditions',
        'pipeline',
        'actif',
    ];

    protected $casts = [
        'conditions' => 'array',
        'pipeline' => 'array',
        'actif' => 'boolean',
    ];

    public function formulaire(): BelongsTo
    {
        return $this->belongsTo(Formulaire::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogExecutionRegle::class, 'regle_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrdrePriorite($query)
    {
        return $query->orderBy('priorite');
    }
}
