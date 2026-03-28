<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use App\Services\NodeService;
use App\Services\EventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function __construct(
        private NodeService $node,
    ) {
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'persona_id' => 'required|uuid',
            'institucion_id' => 'required|uuid',
            'programa_id' => 'required|uuid',
            'titulo_obtenido' => 'required|string',
            'fecha_fin' => 'required|date',
            'fecha_inicio' => 'nullable|date',
            'numero_cedula' => 'nullable|string',
            'titulo_tesis' => 'nullable|string',
            'menciones' => 'nullable|string',
        ]);

        $existe = Transaccion::where('datos->persona_id', $datos['persona_id'])
            ->where('datos->titulo_obtenido', $datos['titulo_obtenido'])
            ->where('datos->fecha_fin', $datos['fecha_fin'])
            ->where('minada', false)
            ->exists();

        if ($existe) {
            EventLogger::log('advertencia', 'Transacción duplicada ignorada', [
                'titulo' => $datos['titulo_obtenido'],
            ]);
            Log::info('[Transaction] Transacción duplicada ignorada', $datos);
            return response()->json([
                'mensaje' => 'Transacción ya existe en este nodo',
            ], 200);
        }

        $transaccion = Transaccion::create([
            'datos' => $datos,
            'minada' => false,
        ]);

        EventLogger::log('transaccion', 'Transacción recibida', [
            'titulo' => $datos['titulo_obtenido'],
        ]);

        Log::info('[Transaction] Nueva transacción recibida', [
            'id' => $transaccion->id,
            'datos' => $datos,
        ]);

        $this->node->propagarTransaccion($datos);

        return response()->json([
            'mensaje' => 'Transacción recibida y propagada',
            'transaccion' => $transaccion,
        ], 201);
    }

    public function index()
    {
        $pendientes = Transaccion::where('minada', false)->get();

        return response()->json([
            'pendientes' => $pendientes,
            'total' => count($pendientes),
        ]);
    }
}