<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Envio imediato
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Defina as 4 propriedades que o jogo precisa
    public function __construct(
        public array $pacman,
        public array $ghost,
        public int $score,
        public array $grid
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('game-room')];
    }
}
