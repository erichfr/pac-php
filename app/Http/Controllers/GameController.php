<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\GameMapService;
use App\Services\GhostAI;
use App\Events\GameStateUpdated;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(GameMapService $mapService): View
    {
        $grid = $mapService->getInitialGrid();
        return view('game', compact('grid'));
    }

    public function tick(Request $request, GhostAI $ghostAI, GameMapService $mapService)
    {
        try {
            $pacman = $request->input('pacman');
            $ghost = $request->input('ghost');

            if (!$pacman || !$ghost) return response()->json(['error' => 'No data'], 400);

            $grid = Cache::get('game_grid', $mapService->getInitialGrid());
            $score = Cache::get('game_score', 0);

            // Calcula próximo passo do fantasma
            $nextGhostMove = $ghostAI->getNextMove($ghost, $pacman, $grid);

            // Dispara o evento para o Reverb (Porta 8000)
            // Agora com os 4 parâmetros combinando com o Evento acima
            broadcast(new GameStateUpdated($pacman, $nextGhostMove, (int)$score, $grid));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Se der erro, ele não "derruba" o servidor, ele te avisa no console
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
