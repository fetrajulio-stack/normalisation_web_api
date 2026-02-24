<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parametre_consigne extends Model
{
    use HasFactory;

    protected $table = 'parametre_consignes'; // nom exact de ta table

    protected $fillable = [
        'codification_id',
        'consigne_id',  // <-- important
        'cle',
        'valeur',
    ];

    public function consigne()
    {
        return $this->belongsTo(Consigne::class);
    }

}
