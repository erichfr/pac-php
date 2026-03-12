<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pac-PHP | Quebrando Barreiras</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        /* Fonte de estilo arcade (opcional se você quiser adicionar o arquivo) */
        @font-face {
            font-family: 'Arcade';
            src: url('/fonts/arcade.ttf') format('truetype');
        }

        body {
            background: #1a202c;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: white;
            font-family: 'Arcade', sans-serif; /* Fallback se a fonte não carregar */
        }
        canvas {
            background: #000;
            box-shadow: 0 0 30px rgba(0,0,0,0.7);
        }
        .ui-panel {
            position: absolute;
            top: 20px;
            width: 100%;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .lives-panel {
            position: absolute;
            bottom: -60px;
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .lives-panel h3 { margin: 0; font-size: 1.2rem; margin-right: 15px; }
        .score, .high-score { font-size: 1.5rem; }
    </style>
</head>
<body>

    <div class="ui-panel text-gray-200">
        <div>
            <div>1UP</div>
            <div class="score font-bold" x-text="score.toString().padStart(3, '0')">000</div>
        </div>
        <div>
            <div>HIGH SCORE</div>
            <div class="high-score font-bold">16440</div>
        </div>
    </div>

    <div x-data="pacmanGame()" x-init="initCanvas()" class="relative">
        <canvas id="gameCanvas" width="420" height="400"></canvas>

        <div class="lives-panel">
            <h3 class="font-bold">LIVES</h3>
            <template x-for="n in lives">
                <div class="draw-life-entity" :style="lifeIconStyle()"></div>
            </template>
            <div class="draw-cherry-entity ml-5" :style="cherryIconStyle()"></div>
        </div>
    </div>

    <script>
        function pacmanGame() {
            return {
                grid: @json($grid),
                // Posição em pixels. Começa no centro da célula 10,15 (200, 300)
                pacman: { x: 200, y: 300, dir: 'stop', nextDir: 'stop', speed: 2 },
                ghost: { x: 200, y: 200, targetX: 200, targetY: 200, speed: 1.5 },
                cellSize: 20,
                score: 0,
                ctx: null,

                initCanvas() {
                    const canvas = document.getElementById('gameCanvas');
                    this.ctx = canvas.getContext('2d');

                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowUp') this.pacman.nextDir = 'up';
                        if (e.key === 'ArrowDown') this.pacman.nextDir = 'down';
                        if (e.key === 'ArrowLeft') this.pacman.nextDir = 'left';
                        if (e.key === 'ArrowRight') this.pacman.nextDir = 'right';
                    });

                    // Escuta o Reverb na porta 8000
                    if (window.Echo) {
                        window.Echo.channel('game-room').listen('GameStateUpdated', (e) => {
                            this.ghost.targetX = e.ghost.x * this.cellSize;
                            this.ghost.targetY = e.ghost.y * this.cellSize;
                            this.score = e.score;
                            this.grid = e.grid;
                        });
                    }

                    this.gameLoop();
                    // Avisa o servidor sobre a posição para a IA do fantasma
                    setInterval(() => this.syncServer(), 150);
                },

                gameLoop() {
                    this.updatePacman();
                    this.updateGhost();
                    this.render();
                    requestAnimationFrame(() => this.gameLoop());
                },

                updatePacman() {
                    // Só tenta mudar de direção ou validar colisão quando estiver no centro de uma célula
                    if (this.pacman.x % this.cellSize === 0 && this.pacman.y % this.cellSize === 0) {
                        const gx = this.pacman.x / this.cellSize;
                        const gy = this.pacman.y / this.cellSize;

                        // Tenta virar para a direção que o usuário quer
                        if (this.canMove(gx, gy, this.pacman.nextDir)) {
                            this.pacman.dir = this.pacman.nextDir;
                        }

                        // Se não pode continuar na direção atual, para
                        if (!this.canMove(gx, gy, this.pacman.dir)) {
                            this.pacman.dir = 'stop';
                        }

                        // Comer comida (local)
                        if (this.grid[gy][gx] === 0) this.grid[gy][gx] = 2;
                    }

                    // Move os pixels
                    if (this.pacman.dir === 'up') this.pacman.y -= this.pacman.speed;
                    if (this.pacman.dir === 'down') this.pacman.y += this.pacman.speed;
                    if (this.pacman.dir === 'left') this.pacman.x -= this.pacman.speed;
                    if (this.pacman.dir === 'right') this.pacman.x += this.pacman.speed;

                    // Teletransporte
                    if (this.pacman.x < 0) this.pacman.x = 400;
                    if (this.pacman.x > 400) this.pacman.x = 0;
                },

                canMove(gx, gy, dir) {
                    if (dir === 'stop') return false;
                    let nx = gx, ny = gy;
                    if (dir === 'up') ny--;
                    if (dir === 'down') ny++;
                    if (dir === 'left') nx--;
                    if (dir === 'right') nx++;
                    return (this.grid[ny] && this.grid[ny][nx] !== 1);
                },

                updateGhost() {
                    // Suaviza o movimento do fantasma até o alvo do PHP
                    if (this.ghost.x < this.ghost.targetX) this.ghost.x += this.ghost.speed;
                    if (this.ghost.x > this.ghost.targetX) this.ghost.x -= this.ghost.speed;
                    if (this.ghost.y < this.ghost.targetY) this.ghost.y += this.ghost.speed;
                    if (this.ghost.y > this.ghost.targetY) this.ghost.y -= this.ghost.speed;
                },

                syncServer() {
                    fetch('/game/tick', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            pacman: { x: Math.round(this.pacman.x/20), y: Math.round(this.pacman.y/20) },
                            ghost: { x: Math.round(this.ghost.x/20), y: Math.round(this.ghost.y/20) }
                        })
                    });
                },

                render() {
                    this.ctx.fillStyle = '#000';
                    this.ctx.fillRect(0, 0, 420, 400);
                    this.drawMap();
                    this.drawPacman();
                    this.drawGhost();
                },

                drawPacman() {
                    const cx = this.pacman.x + 10;
                    const cy = this.pacman.y + 10;
                    this.ctx.save();
                    this.ctx.translate(cx, cy);

                    // Rotação baseada na direção
                    const angles = { 'right': 0, 'down': 0.5, 'left': 1, 'up': 1.5 };
                    if (this.pacman.dir !== 'stop') {
                        this.ctx.rotate(angles[this.pacman.dir] * Math.PI);
                    }

                    this.ctx.fillStyle = '#fbbf24';
                    this.ctx.beginPath();
                    const mouth = (Math.sin(Date.now() / 100) + 1) * 0.2;
                    this.ctx.arc(0, 0, 8, mouth * Math.PI, (2 - mouth) * Math.PI);
                    this.ctx.lineTo(0, 0);
                    this.ctx.fill();
                    this.ctx.restore();
                },

                drawGhost() {
                    this.ctx.fillStyle = '#f87171';
                    this.ctx.beginPath();
                    this.ctx.arc(this.ghost.x + 10, this.ghost.y + 10, 8, 0, Math.PI * 2);
                    this.ctx.fill();
                },

                drawMap() {
                    this.grid.forEach((row, y) => {
                        row.forEach((cell, x) => {
                            if (cell === 1) {
                                this.ctx.strokeStyle = '#1e3a8a';
                                this.ctx.strokeRect(x * 20 + 2, y * 20 + 2, 16, 16);
                            } else if (cell === 0) {
                                this.ctx.fillStyle = '#fbbf24';
                                this.ctx.beginPath();
                                this.ctx.arc(x * 20 + 10, y * 20 + 10, 2, 0, Math.PI * 2);
                                this.ctx.fill();
                            }
                        });
                    });
                }
            }
        }
        </script>
</body>
</html>
