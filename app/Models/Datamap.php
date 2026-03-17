<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Datamap extends Model
{
    use HasFactory;
    protected $table = 'datamaps';

    protected $fillable = [
        'position',
        'longueur',
        'codification_id',
        'champ_id'
    ];

    public function codification()
    {
        return $this->belongsTo(Codification::class);
    }

    public function champ()
    {
        return $this->belongsTo(Champ::class);
    }
}
