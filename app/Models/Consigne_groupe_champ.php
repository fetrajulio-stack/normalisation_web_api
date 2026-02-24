<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consigne_groupe_champ extends Model
{
    use HasFactory;

    protected $table = 'consigne_groupe_champ';
    // ⬅️ adapte si ta table s'appelle sans "s"

    protected $fillable = [
        'consigne_groupe_id',
        'champ_id',
        'ordre',
    ];

    public function groupe()
    {
        return $this->belongsToMany(
            Champ::class,
            'consigne_groupe_champ'
        )->withPivot('ordre')
            ->orderBy('pivot_ordre');
    }

    public function champ()
    {
        return $this->belongsTo(Champ::class);
    }
}
