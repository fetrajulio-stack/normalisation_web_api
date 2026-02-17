<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consigne extends Model
{
    use HasFactory;

    public function codifications()
    {
        return $this->belongsToMany(
            Codification::class,
            'champs'
        )->withTimestamps();
    }

    public function champs()
    {
        return $this->hasMany(Champ::class);
    }

    public function parametres()
    {
        return $this->hasMany(Parametre_consigne::class);
    }

    public function groupes()
    {
        return $this->hasMany(Consigne_groupe::class);
    }



}
