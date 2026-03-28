<?php

namespace App\Services;

use App\Models\Grado;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Nodo;
use App\Services\EventLogger;

class ConsensusService
{
    public function __construct(
        private BlockchainService $blockchain
    ) {
    }

    public function resolver(): array
    {
        $nodos = Nodo::where('activo', true)->get();
        $cadenaActual = Grado::orderBy('creado_en')->get()->toArray();
        $longitudMaxima = count($cadenaActual);
        $nuevaCadena = null;
        $nodoGanador = null;

        foreach ($nodos as $nodo) {
            try {
                $response = Http::timeout(5)->get("{$nodo->url}/api/chain");

                if (!$response->ok())
                    continue;

                $cadenaRemota = $response->json('chain');

                if (
                    is_array($cadenaRemota) &&
                    count($cadenaRemota) > $longitudMaxima &&
                    $this->blockchain->cadenaEsValida($cadenaRemota)
                ) {
                    $longitudMaxima = count($cadenaRemota);
                    $nuevaCadena = $cadenaRemota;
                    $nodoGanador = $nodo->url;
                }
            } catch (\Exception $e) {
                Log::error('[Consensus] Error consultando nodo', [
                    'nodo' => $nodo->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($nuevaCadena) {
            $this->reemplazarCadena($nuevaCadena);
            Log::info('[Consensus] Cadena reemplazada', ['nodo_ganador' => $nodoGanador]);

            return [
                'reemplazada' => true,
                'mensaje' => 'Cadena reemplazada por una más larga y válida',
                'nodo_fuente' => $nodoGanador,
                'longitud' => $longitudMaxima,
            ];
        }

        Log::info('[Consensus] Cadena local es la más larga');

        return [
            'reemplazada' => false,
            'mensaje' => 'Esta cadena ya es la más larga',
            'longitud' => $longitudMaxima,
        ];

        EventLogger::log('consenso', 'Cadena reemplazada', [
            'fuente' => $nodoGanador,
            'longitud' => $longitudMaxima,
        ]);

        EventLogger::log('consenso', 'Cadena local es la más larga', [
            'longitud' => $longitudMaxima,
        ]);

    }

    private function reemplazarCadena(array $nuevaCadena): void
    {
        Grado::truncate();

        foreach ($nuevaCadena as $bloque) {
            Grado::create($bloque);
        }
    }
}