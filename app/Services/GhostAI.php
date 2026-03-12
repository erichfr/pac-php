<?php

namespace App\Services;

class GhostAI
{
    /**
     * Calcula o próximo passo do fantasma em direção ao alvo (Pacman).
     * Implementação simplificada de busca gananciosa (Greedy Search).
     */
    public function getNextMove(array $ghostPos, array $pacmanPos, array $grid): array
    {
        $possibleMoves = [
            ['x' => $ghostPos['x'], 'y' => $ghostPos['y'] - 1], // Cima
            ['x' => $ghostPos['x'], 'y' => $ghostPos['y'] + 1], // Baixo
            ['x' => $ghostPos['x'] - 1, 'y' => $ghostPos['y']], // Esquerda
            ['x' => $ghostPos['x'] + 1, 'y' => $ghostPos['y']], // Direita
        ];

        $bestMove = $ghostPos;
        $minDistance = INF;

        foreach ($possibleMoves as $move) {
            // Verifica se é uma parede (1) no nosso grid
            if (($grid[$move['y']][$move['x']] ?? 1) === 1) {
                continue;
            }

            // Cálculo de Distância Euclidiana (Matemática pura no PHP!)
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
