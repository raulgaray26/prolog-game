<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

/*
Dos rutas únicas: GET para mostrar la consola y POST para procesar la consulta.
*/

Route::get('/', [GameController::class, 'index'])->name('game.index');
Route::post('/query', [GameController::class, 'query'])->name('game.query');