<?php

use App\Http\Controllers\BlockchainController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\EventoController;
use Illuminate\Support\Facades\Route;

Route::get('/chain', [BlockchainController::class, 'chain']);
Route::post('/mine', [BlockchainController::class, 'mine']);
Route::get('/nodes/resolve', [BlockchainController::class, 'resolve']);
Route::post('/bloques/recibir', [BlockchainController::class, 'recibirBloque']);

Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/transactions', [TransactionController::class, 'index']);

Route::post('/nodes/register', [NodeController::class, 'register']);
Route::get('/nodes', [NodeController::class, 'index']);

Route::get('/eventos', [EventoController::class, 'stream']);