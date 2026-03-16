<?php

namespace App\Services;

class GhostAI
{
    public function getNextMove($ghostPos, $pacmanPos, $grid): array
    {
        // Se algum dado estiver corrompido, o fantasma fica parado
        if (!is_array($ghostPos) || !is_array($pacmanPos)) {
            return ['x' => 10, 'y' => 10];
        }

        $possibleMoves = [
            ['x' => $ghostPos['x'], 'y' => $ghostPos['y'] - 1], // Cima
            ['x' => $ghostPos['x'], 'y' => $ghostPos['y'] + 1], // Baixo
            ['x' => $ghostPos['x'] - 1, 'y' => $ghostPos['y']], // Esquerda
            ['x' => $ghostPos['x'] + 1, 'y' => $ghostPos['y']], // Direita
        ];

        $bestMove = $ghostPos;
        $minDistance = INF;

        foreach ($possibleMoves as $move) {
            // Verifica se a linha e a coluna existem no grid antes de checar se é parede
            $row = $grid[$move['y']] ?? null;
            $cell = $row[$move['x']] ?? 1;

            if ($cell === 1) {
                continue;
            }

            // Distância Euclidiana
            $distance = sqrt(
                pow($pacmanPos['x'] - $move['x'], 2) +
                pow($pacmanPos['y'] - $move['y'], 2)
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMove = $move;
            }
        }

        return $bestMove;
    }
}
