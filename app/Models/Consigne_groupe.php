<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consigne_groupe extends Model
{
    use HasFactory;
    protected $table = 'consigne_groupes';

    public function champs()
    {
        return $this->belongsToMany(
            Champ::class,
            'consigne_groupe_champ'
        )->orderBy('ordre');
    }

    public function consigne()
    {
        return $this->belongsTo(Consigne::class);
    }
}

