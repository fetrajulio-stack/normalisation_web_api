<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parametre_consigne extends Model
{
    use HasFactory;

    public function consigne()
    {
        return $this->belongsTo(Consigne::class);
    }

}
