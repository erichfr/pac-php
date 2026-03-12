<?php

namespace Tests\Unit;

use App\Services\GhostAI;

test('o fantasma decide se mover para perto do pacman', function () {
    $ai = new GhostAI();
    $grid = [
        [1, 1, 1, 1, 1],
        [1, 0, 0, 0, 1], // Caminho livre na linha 1
        [1, 1, 1, 1, 1],
    ];

    $ghostPos = ['x' => 1, 'y' => 1];
    $pacmanPos = ['x' => 3, 'y' => 1];

    $nextMove = $ai->getNextMove($ghostPos, $pacmanPos, $grid);

    // O próximo passo lógico para chegar em (3,1) saindo de (1,1) é (2,1)
    expect($nextMove)->toBe(['x' => 2, 'y' => 1]);
});
