<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationPrimitive extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'description',
        'parametres_schema',
        'classe_php',
        'actif',
    ];

    protected $casts = [
        'parametres_schema' => 'array',
        'actif' => 'boolean',
    ];

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Instancie la classe d'opération
     */
    public function getInstance()
    {
        if (!$this->classe_php || !class_exists($this->classe_php)) {
            throw new \Exception("Classe d'opération introuvable : {$this->classe_php}");
        }

        return new $this->classe_php();
    }
}
