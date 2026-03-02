<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'matricule',
        'actif',
        'profil_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function profil()
    {
        return $this->belongsTo(Profil::class);
    }
}
