<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    protected $fillable = [
        'datos',
        'minada',
    ];

    protected $casts = [
        'datos'  => 'array',
        'minada' => 'boolean',
    ];
}