<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeCover extends Model
{
    //
    protected $fillable = [
        'anime_id',
        'cover_url',
        'path',
        'filename',
        'hash',
    ];

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
