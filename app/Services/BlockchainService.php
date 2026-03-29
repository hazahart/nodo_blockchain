<?php

namespace App\Services;

use App\Models\Grado;
use App\Models\Transaccion;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    const DIFICULTAD = '000';

    public function calcularHash(array $datos): string
    {
        $cadena = implode('', [
            $datos['persona_id'] ?? '',
            $datos['institucion_id'] ?? '',
            $datos['titulo_obtenido'] ?? '',
            $datos['fecha_fin'] ?? '',
            $datos['hash_anterior'] ?? '',
            $datos['nonce'] ?? '',
        ]);

        return hash('sha256', $cadena);
    }

    public function minar(array $datos, string $hashAnterior): array
    {
        $nonce = 0;
        $datos['hash_anterior'] = $hashAnterior;

        do {
            $datos['nonce'] = $nonce;
            $hash = $this->calcularHash($datos);
            $nonce++;
        } while (!str_starts_with($hash, self::DIFICULTAD));

        Log::info('[Blockchain] Bloque minado', [
            'nonce' => $datos['nonce'],
            'hash' => $hash,
        ]);

        $datos['hash_actual'] = $hash;

        EventLogger::log('minando', 'Iniciando Proof of Work', [
            'hash_anterior' => $hashAnterior,
        ]);

        EventLogger::log('bloque_minado', 'Bloque minado correctamente', [
            'nonce' => $datos['nonce'],
            'hash' => substr($hash, 0, 16) . '...',
        ]);

        return $datos;
    }

    public function esHashValido(array $bloque): bool
    {
        $hashEsperado = $this->calcularHash($bloque);
        return $bloque['hash_actual'] === $hashEsperado
            && str_starts_with($bloque['hash_actual'], self::DIFICULTAD);
    }

    public function cadenaEsValida(array $cadena): bool
    {
        for ($i = 1; $i < count($cadena); $i++) {
            $bloque = $cadena[$i];
            $anterior = $cadena[$i - 1];

            if ($bloque['hash_anterior'] !== $anterior['hash_actual']) {
                Log::warning('[Blockchain] hash_anterior no coincide', ['bloque' => $i]);
                return false;
            }

            if (!$this->esHashValido($bloque)) {
                Log::warning('[Blockchain] Hash inválido en bloque', ['bloque' => $i]);
                return false;
            }
        }

        return true;
    }

    public function obtenerUltimoHash(): string
    {
        $ultimo = Grado::orderBy('creado_en', 'desc')->first();
        return $ultimo ? $ultimo->hash_actual : '0';
    }

    public function crearBloque(Transaccion $transaccion): Grado
    {
        $datos = $transaccion->datos;
        $hashAnterior = $this->obtenerUltimoHash();
        $datosMinados = $this->minar($datos, $hashAnterior);

        $grado = Grado::create([
            'persona_id' => $datosMinados['persona_id'] ?? null,
            'institucion_id' => $datosMinados['institucion_id'] ?? null,
            'programa_id' => $datosMinados['programa_id'] ?? null,
            'fecha_inicio' => $datosMinados['fecha_inicio'] ?? null,
            'fecha_fin' => $datosMinados['fecha_fin'] ?? null,
            'titulo_obtenido' => $datosMinados['titulo_obtenido'] ?? null,
            'numero_cedula' => $datosMinados['numero_cedula'] ?? null,
            'titulo_tesis' => $datosMinados['titulo_tesis'] ?? null,
            'menciones' => $datosMinados['menciones'] ?? null,
            'hash_actual' => $datosMinados['hash_actual'],
            'hash_anterior' => $datosMinados['hash_anterior'],
            'nonce' => $datosMinados['nonce'],
            'firmado_por' => $datosMinados['firmado_por'] ?? 'nodo-laravel',
        ]);

        $transaccion->update(['minada' => true]);

        return $grado;
    }
}