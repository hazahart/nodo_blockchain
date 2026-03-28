<?php

namespace App\Services;

class EventLogger
{
    const ARCHIVO = 'eventos.log';

    public static function log(string $tipo, string $mensaje, array $datos = []): void
    {
        $evento = json_encode([
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'datos' => $datos,
            'nodo' => config('app.nodo_nombre', 'nodo-laravel'),
            'timestamp' => now()->toISOString(),
        ]);

        file_put_contents(
            storage_path('logs/' . self::ARCHIVO),
            $evento . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}