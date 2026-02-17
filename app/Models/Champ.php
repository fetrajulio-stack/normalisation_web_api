<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Champ extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_champ',
        'codification_id',
        'consigne_id'
    ];

    public function consigne()
    {
        return $this->belongsTo(Consigne::class);
    }

    public function codification()
    {
        return $this->belongsTo(Codification::class);
    }
}
