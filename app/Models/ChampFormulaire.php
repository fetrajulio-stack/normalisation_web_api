<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChampFormulaire extends Model
{
    use HasFactory;
    protected $table = 'champs_formulaire';

    protected $fillable = [
        'formulaire_id',
        'code_champ',
        'libelle',
        'type_champ',
        'valeurs_possibles',
        'obligatoire',
        'ordre_affichage',
    ];

    protected $casts = [
        'valeurs_possibles' => 'array',
        'obligatoire' => 'boolean',
    ];

    public function formulaire(): BelongsTo
    {
        return $this->belongsTo(Formulaire::class);
    }

    public function groupes(): BelongsToMany
    {
        return $this->belongsToMany(GroupeChamp::class, 'champs_groupes', 'champ_id', 'groupe_id')
            ->withPivot('ordre_dans_groupe')
            ->withTimestamps();
    }

}
