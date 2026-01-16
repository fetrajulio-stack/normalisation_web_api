<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogExecutionRegle extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Pas de updated_at

    protected $fillable = [
        'regle_id',
        'enregistrement_id',
        'succes',
        'erreur',
        'duree_ms',
    ];

    protected $casts = [
        'succes' => 'boolean',
    ];

    public function regle(): BelongsTo
    {
        return $this->belongsTo(RegleExport::class, 'regle_id');
    }

    public function enregistrement(): BelongsTo
    {
        return $this->belongsTo(Enregistrement::class);
    }
}
