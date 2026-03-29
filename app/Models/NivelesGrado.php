<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NivelesGrado extends Model
{
    public $timestamps = false;
    protected $table = 'niveles_grados';

    protected $fillable = ['nombre'];

    public function programas()
    {
        return $this->hasMany(Programa::class);
    }
}