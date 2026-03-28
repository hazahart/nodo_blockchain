<?php

namespace App\Http\Controllers;

class EventoController extends Controller
{
    public function stream()
    {
        return response()->stream(function () {
            $archivo = storage_path('logs/eventos.log');
            $position = file_exists($archivo) ? filesize($archivo) : 0;
            $intentos = 0;

            while (true) {
                if ($intentos % 25 === 0) {
                    echo "event: heartbeat\ndata: ping\n\n";
                    ob_flush();
                    flush();
                }

                clearstatcache(true, $archivo);

                if (file_exists($archivo)) {
                    $tamano = filesize($archivo);

                    if ($tamano > $position) {
                        $file = fopen($archivo, 'r');
                        fseek($file, $position);
                        $nuevas = fread($file, $tamano - $position);
                        fclose($file);
                        $position = $tamano;

                        foreach (explode("\n", trim($nuevas)) as $linea) {
                            if (empty(trim($linea)))
                                continue;
                            $evento = json_decode($linea, true);
                            if (!$evento)
                                continue;

                            echo "event: actividad\n";
                            echo "data: " . json_encode($evento) . "\n\n";
                            ob_flush();
                            flush();
                        }
                    }
                }

                if (connection_aborted())
                    break;
                sleep(1);
                $intentos++;
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}