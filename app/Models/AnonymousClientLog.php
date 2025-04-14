<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnonymousClientLog extends Model
{
    protected $fillable = [
        'anonymous_client_id',
        'method',
        'path',
        'full_url',
        'query_params',
    ];

    protected $casts = [
        'query_params' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(AnonymousClient::class, 'anonymous_client_id');
    }
}
