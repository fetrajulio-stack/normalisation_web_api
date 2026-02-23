<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dossier extends Model
{
    protected $connection = 'pgsql_auth';
    protected $table = 'dossier';
    protected $primaryKey = 'id_dossier';
    public $timestamps = false;

    public function cathegories()
    {
        return $this->hasMany(
            Cathegorie::class,
            'id_dossier',
            'id_dossier'
        )->where('actif', 1);
    }
}
