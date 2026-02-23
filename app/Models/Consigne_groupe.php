<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consigne_groupe extends Model
{
    use HasFactory;
    protected $table = 'consigne_groupes';
    // ajouter les colonnes autorisées pour create/update mass assign
    protected $fillable = [
        'consigne_id',
        'ordre_execution', // si ta colonne s'appelle comme ça dans la table
    ];
    public function champs()
    {
        /**return $this->belongsToMany(
            Champ::class,
            'consigne_groupe_champ'
        )->orderBy('ordre');*/
        return $this->hasMany(Consigne_groupe_champ::class)
                    ->orderBy('ordre');;
    }

    public function consigne()
    {
        return $this->belongsTo(Consigne::class);
    }

    public function parametres()
    {
        return $this->hasMany(Parametre_consigne::class,'consigne_id','consigne_id');
    }

}

