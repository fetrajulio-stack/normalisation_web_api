<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Codification extends Model
{
    use HasFactory;

    public function parametre()
    {
        return $this->hasOne(Parametre::class);
    }

    public function consignes()
    {
        return $this->belongsToMany(
            Consigne::class,
            'champs'
        )->withTimestamps();
    }

}
