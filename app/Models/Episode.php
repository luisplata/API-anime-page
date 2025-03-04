<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = ['anime_id', 'number', 'title'];

    // Relationship with Anime (Many Episodes belong to One Anime)
    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }

    // Relationship with EpisodeSource (One Episode has Many Sources)
    public function sources()
    {
        return $this->hasMany(EpisodeSource::class);
    }
}
