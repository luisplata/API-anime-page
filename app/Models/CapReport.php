<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapReport extends Model
{

    protected $fillable = [
        'episode_id',
        'reason',
        'description',
        'reported_by',
        'resolved',
    ];

    // Relación: Un reporte pertenece a un episodio
    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}