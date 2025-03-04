<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anime extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'image'];

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
