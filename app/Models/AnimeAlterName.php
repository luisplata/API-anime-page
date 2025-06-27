<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeAlterName extends Model
{
    protected $fillable = ['anime_id', 'name'];

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
