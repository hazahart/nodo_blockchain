<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NivelGradoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('niveles_grados')->insert([
            ['nombre' => 'Técnico'],
            ['nombre' => 'Licenciatura'],
            ['nombre' => 'Maestría'],
            ['nombre' => 'Doctorado'],
            ['nombre' => 'Especialidad'],
        ]);
    }
}