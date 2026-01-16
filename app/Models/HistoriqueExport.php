<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueExport extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Pas de updated_at

    protected $fillable = [
        'formulaire_id',
        'nombre_enregistrements',
        'regles_appliquees',
        'utilisateur_id',
        'format_export',
        'fichier_genere',
    ];

    protected $casts = [
        'regles_appliquees' => 'array',
    ];

    public function formulaire(): BelongsTo
    {
        return $this->belongsTo(Formulaire::class);
    }
}
