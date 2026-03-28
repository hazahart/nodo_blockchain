<?php

namespace App\Services;

use App\Models\Nodo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\EventLogger;

class NodeService
{
    public function propagarTransaccion(array $datos): void
    {
        $nodos = Nodo::where('activo', true)->get();

        foreach ($nodos as $nodo) {
            try {
                Http::timeout(5)->post("{$nodo->url}/api/transactions", $datos);
                Log::info('[Node] Transacción propagada', ['nodo' => $nodo->url]);
            } catch (\Exception $e) {
                Log::error('[Node] Error propagando transacción', [
                    'nodo' => $nodo->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        EventLogger::log('propagacion', "Propagado a {$nodo->url}", [
            'nodo' => $nodo->nombre ?? $nodo->url,
        ]);

        EventLogger::log('error', "Error propagando a {$nodo->url}", [
            'error' => $e->getMessage(),
        ]);
    }

    public function propagarBloque(array $bloque): void
    {
        $nodos = Nodo::where('activo', true)->get();

        foreach ($nodos as $nodo) {
            try {
                Http::timeout(5)->post("{$nodo->url}/api/bloques/recibir", $bloque);
                Log::info('[Node] Bloque propagado', ['nodo' => $nodo->url]);
            } catch (\Exception $e) {
                Log::error('[Node] Error propagando bloque', [
                    'nodo' => $nodo->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function registrarNodo(string $url, string $nombre = null): Nodo
    {
        $nodo = Nodo::firstOrCreate(
            ['url' => rtrim($url, '/')],
            ['nombre' => $nombre, 'activo' => true]
        );

        Log::info('[Node] Nodo registrado', ['url' => $url]);

        return $nodo;
    }
}