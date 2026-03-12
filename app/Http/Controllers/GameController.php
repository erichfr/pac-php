<?php

namespace App\Http\Controllers;

use App\Events\GameStateUpdated;
use App\Services\GameMapService;
use App\Services\GhostAI;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameController extends Controller
{
    public function index(GameMapService $mapService): View
    {
        // Pegamos o mapa que definimos no Service
        $grid = $mapService->getInitialGrid();

        return view('game', compact('grid'));
    }

    // No método move() do GameController
    public function move(Request $request, GhostAI $ghostAI, GameMapService $mapService)
    {
        $grid = Cache::remember('game_grid', 3600, fn() => $mapService->getInitialGrid());
        $score = Cache::get('game_score', 0);

        $pacman = $request->input('pacman');
        $ghost = $request->input('ghost');

        // --- LÓGICA DE TELETRANSPORTE (Túneis) ---
        if ($pacman['x'] < 0) $pacman['x'] = 20;
        if ($pacman['x'] > 20) $pacman['x'] = 0;

        // --- LÓGICA DE COMER (Imediata no PHP) ---
        if (isset($grid[$pacman['y']][$pacman['x']])) {
            if ($grid[$pacman['y']][$pacman['x']] === 0) { // Pastilha
                $grid[$pacman['y']][$pacman['x']] = 2;
                $score += 10;
            }
        }

        $nextGhostMove = $ghostAI->getNextMove($ghost, $pacman, $grid);

        Cache::put('game_grid', $grid, 3600);
        Cache::put('game_score', $score, 3600);

        broadcast(new GameStateUpdated($pacman, $nextGhostMove, $score, $grid));

        return response()->json(['score' => $score]);
    }

    public function tick(Request $request, GhostAI $ghostAI, GameMapService $mapService)
    {
        // Recupera o estado do cache
        $grid = Cache::get('game_grid', $mapService->getInitialGrid());
        $score = Cache::get('game_score', 0);

        $pacman = $request->input('pacman');
        $ghost = $request->input('ghost');

        // IA do Fantasma
        $nextGhostMove = $ghostAI->getNextMove($ghost, $pacman, $grid);

        // Se o Pac-Man comeu algo na posição dele, atualizamos o score e o grid
        if ($grid[$pacman['y']][$pacman['x']] === 0) {
            $grid[$pacman['y']][$pacman['x']] = 2;
            $score += 10;
            Cache::put('game_grid', $grid, 3600);
            Cache::put('game_score', $score, 3600);
        }

        // DISPARA O EVENTO (O Reverb na porta 8000 vai receber isso)
        broadcast(new GameStateUpdated($pacman, $nextGhostMove, $score, $grid));

        return response()->json(['success' => true]);
    }
}
