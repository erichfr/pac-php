<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index']);
Route::post('/game/move', [GameController::class, 'move']);
Route::post('/game/tick', [GameController::class, 'tick']);
