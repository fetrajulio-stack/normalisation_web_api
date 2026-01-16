<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formulaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function champs(): HasMany
    {
        return $this->hasMany(ChampFormulaire::class);
    }

    public function groupes(): HasMany
    {
        return $this->hasMany(GroupeChamp::class);
    }

    public function regles(): HasMany
    {
        return $this->hasMany(RegleExport::class)->orderBy('priorite');
    }

    public function enregistrements(): HasMany
    {
        return $this->hasMany(Enregistrement::class);
    }

    public function historiquesExports(): HasMany
    {
        return $this->hasMany(HistoriqueExport::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
