<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LastPagination extends Model
{
    protected $fillable = [
        'type',
        'page',
    ];
}
