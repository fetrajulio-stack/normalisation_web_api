<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cathegorie extends Model
{
    protected $connection = 'pgsql_auth';
    protected $table = 'cathegorie';
    protected $primaryKey = 'id_code_dossier';
    public $timestamps = false;
}


