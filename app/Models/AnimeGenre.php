<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeGenre extends Model
{
    protected $fillable = ['anime_id', 'genre'];

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
