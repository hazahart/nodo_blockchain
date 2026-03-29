<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use App\Models\Transaccion;
use App\Services\BlockchainService;
use App\Services\ConsensusService;
use App\Services\NodeService;
use Illuminate\Support\Facades\Log;
use App\Services\EventLogger;

class BlockchainController extends Controller
{
    public function __construct(
        private BlockchainService $blockchain,
        private ConsensusService $consensus,
        private NodeService $node,
    ) {
    }

    public function chain()
    {
        $cadena = Grado::orderBy('creado_en')->get();

        Log::info('[Chain] Cadena consultada', ['longitud' => count($cadena)]);

        return response()->json([
            'chain' => $cadena,
            'longitud' => count($cadena),
        ]);
    }

    public function mine()
    {
        $pendientes = Transaccion::where('minada', false)->get();

        if ($pendientes->isEmpty()) {
            return response()->json([
                'mensaje' => 'No hay transacciones pendientes para minar',
            ], 200);
        }

        $bloques = [];

        foreach ($pendientes as $transaccion) {
            $bloque = $this->blockchain->crearBloque($transaccion);
            $bloques[] = $bloque;

            $this->node->propagarBloque($bloque->toArray());
        }

        Log::info('[Mine] Bloques minados', ['cantidad' => count($bloques)]);

        return response()->json([
            'mensaje' => 'Bloques minados correctamente',
            'bloques' => $bloques,
        ]);
    }

    public function resolve()
    {
        $resultado = $this->consensus->resolver();

        return response()->json($resultado);
    }

    public function recibirBloque()
    {
        $datos = request()->all();

        if (
            empty($datos['hash_actual']) ||
            empty($datos['hash_anterior']) ||
            !$this->blockchain->esHashValido($datos)
        ) {
            Log::warning('[Bloque] Bloque recibido inválido', $datos);

            EventLogger::log('error', 'Bloque inválido rechazado', []);

            return response()->json([
                'mensaje' => 'Bloque inválido rechazado',
            ], 422);
        }

        $ultimoHashLocal = $this->blockchain->obtenerUltimoHash();

        if ($ultimoHashLocal !== '0' && $datos['hash_anterior'] !== $ultimoHashLocal) {
            Log::warning('[Bloque] Desincronización detectada', [
                'esperado' => $ultimoHashLocal, 
                'recibido' => $datos['hash_anterior']
            ]);
            
            return response()->json([
                'mensaje' => 'El hash_anterior no coincide con la cadena local. Se requiere resolver conflictos (consenso).'
            ], 409);
        }

        $existe = Grado::where('hash_actual', $datos['hash_actual'])->exists();

        if ($existe) {
            return response()->json([
                'mensaje' => 'Bloque ya existe en esta cadena',
            ], 200);
        }

        Grado::create($datos);

        Log::info('[Bloque] Bloque recibido y aceptado', [
            'hash' => $datos['hash_actual'],
        ]);

        EventLogger::log('bloque_recibido', 'Bloque recibido y validado', [
            'hash' => substr($datos['hash_actual'], 0, 16) . '...',
        ]);

        return response()->json([
            'mensaje' => 'Bloque aceptado y agregado a la cadena',
        ], 201);
    }
}