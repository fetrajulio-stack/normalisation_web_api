<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GroupeChamp extends Model
{
    use HasFactory;

    protected $table = 'groupes_champs';

    protected $fillable = [
        'formulaire_id',
        'nom_groupe',
        'description',
    ];

    public function formulaire(): BelongsTo
    {
        return $this->belongsTo(Formulaire::class);
    }

    public function champs(): BelongsToMany
    {
        return $this->belongsToMany(ChampFormulaire::class, 'champs_groupes', 'groupe_id', 'champ_id')
            ->withPivot('ordre_dans_groupe')
            ->orderBy('champs_groupes.ordre_dans_groupe')
            ->withTimestamps();
    }
}
