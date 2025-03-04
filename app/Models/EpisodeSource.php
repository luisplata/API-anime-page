<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpisodeSource extends Model
{
    use HasFactory;

    protected $fillable = ['episode_id', 'quality', 'url'];

    // Relationship with Episode (Many Sources belong to One Episode)
    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
