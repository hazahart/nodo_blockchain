<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Programa extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    const CREATED_AT = 'creado_en';

    protected $fillable = [
        'nombre',
        'nivel_grado_id',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = (string) Str::uuid());
    }

    public function nivelGrado()
    {
        return $this->belongsTo(NivelGrado::class);
    }

    public function grados()
    {
        return $this->hasMany(Grado::class);
    }
}