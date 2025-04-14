<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnonymousClient extends Model
{
    //
    public $incrementing = false; // Porque usamos UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'ip', 'user_agent',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(AnonymousClientLog::class);
    }
}
