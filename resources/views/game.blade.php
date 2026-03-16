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
        // --- Estado Inicial ---
        grid: @json($grid),
        pacman: {
            x: 200,
            y: 300,
            dir: 'stop',
            nextDir: 'stop',
            speed: 2,
            currentSpeed: 0
        },
        ghost: {
            x: 200,
            y: 160,
            targetX: 200,
            targetY: 160,
            speed: 1.5
        },
        cellSize: 20,
        score: 0,
        lives: 3,
        ctx: null,

        // --- Estilos da UI ---
        lifeIconStyle() {
            return 'width: 20px; height: 20px; background-color: #fbbf24; border-radius: 50%; display: inline-block;';
        },
        cherryIconStyle() {
            return 'width: 20px; height: 20px; background-color: #f87171; border-radius: 50%; display: inline-block;';
        },

        // --- Inicialização ---
        initCanvas() {
            const canvas = document.getElementById('gameCanvas');
            this.ctx = canvas.getContext('2d');

            window.addEventListener('keydown', (e) => {
                const keys = {
                    'ArrowUp': 'up',
                    'ArrowDown': 'down',
                    'ArrowLeft': 'left',
                    'ArrowRight': 'right'
                };
                if (keys[e.key]) {
                    this.pacman.nextDir = keys[e.key];
                    e.preventDefault(); // Evita scroll da página
                }
            });

            if (window.Echo) {
                window.Echo.channel('game-room').listen('GameStateUpdated', (e) => {
                    this.ghost.targetX = e.ghost.x * this.cellSize;
                    this.ghost.targetY = e.ghost.y * this.cellSize;
                    this.score = e.score;
                    this.grid = e.grid;
                });
            }

            this.gameLoop();
            setInterval(() => this.syncServer(), 150);
        },

        // --- Ciclo de Jogo ---
        gameLoop() {
            this.updatePacman();
            this.updateGhost();
            this.render();
            requestAnimationFrame(() => this.gameLoop());
        },

        updatePacman() {
            // Verifica alinhamento com a grade para permitir virar ou parar
            if (this.pacman.x % this.cellSize === 0 && this.pacman.y % this.cellSize === 0) {
                const gx = Math.round(this.pacman.x / this.cellSize);
                const gy = Math.round(this.pacman.y / this.cellSize);

                // Tenta mudar para a direção desejada
                if (this.canMove(gx, gy, this.pacman.nextDir)) {
                    this.pacman.dir = this.pacman.nextDir;
                }

                // Verifica se pode continuar andando
                if (!this.canMove(gx, gy, this.pacman.dir)) {
                    this.pacman.currentSpeed = 0;
                } else {
                    this.pacman.currentSpeed = this.pacman.speed;
                }

                // Lógica de comer (local para feedback rápido)
                if (this.grid[gy] && this.grid[gy][gx] === 0) {
                    this.grid[gy][gx] = 2;
                }
            }

            // Movimentação em pixels
            if (this.pacman.currentSpeed > 0) {
                if (this.pacman.dir === 'up')    this.pacman.y -= this.pacman.currentSpeed;
                if (this.pacman.dir === 'down')  this.pacman.y += this.pacman.currentSpeed;
                if (this.pacman.dir === 'left')  this.pacman.x -= this.pacman.currentSpeed;
                if (this.pacman.dir === 'right') this.pacman.x += this.pacman.currentSpeed;
            }

            // Teletransporte nos túneis
            if (this.pacman.x < 0) this.pacman.x = 400;
            if (this.pacman.x > 400) this.pacman.x = 0;
        },

        updateGhost() {
            // Movimento suave do fantasma até a coordenada do servidor
            if (this.ghost.x < this.ghost.targetX) this.ghost.x += this.ghost.speed;
            if (this.ghost.x > this.ghost.targetX) this.ghost.x -= this.ghost.speed;
            if (this.ghost.y < this.ghost.targetY) this.ghost.y += this.ghost.speed;
            if (this.ghost.y > this.ghost.targetY) this.ghost.y -= this.ghost.speed;
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

        // --- Comunicação com Laravel ---
        syncServer() {
            // Enviamos apenas coordenadas INTEIRAS (0 a 20) para o PHP não quebrar
            const pX = Math.round(this.pacman.x / this.cellSize);
            const pY = Math.round(this.pacman.y / this.cellSize);
            const gX = Math.round(this.ghost.x / this.cellSize);
            const gY = Math.round(this.ghost.y / this.cellSize);

            fetch('/game/tick', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    pacman: { x: pX, y: pY },
                    ghost: { x: gX, y: gY }
                })
            }).catch(err => console.error("Erro na sincronização:", err));
        },

        // --- Renderização ---
        render() {
            this.ctx.fillStyle = '#000';
            this.ctx.fillRect(0, 0, 420, 400);
            this.drawMap();
            this.drawPacman();
            this.drawGhost();
        },

        drawMap() {
            this.grid.forEach((row, y) => {
                row.forEach((cell, x) => {
                    if (cell === 1) { // Parede
                        this.ctx.strokeStyle = '#1e3a8a';
                        this.ctx.lineWidth = 2;
                        this.ctx.strokeRect(x * 20 + 2, y * 20 + 2, 16, 16);
                    } else if (cell === 0) { // Pastilha
                        this.ctx.fillStyle = '#fbbf24';
                        this.ctx.beginPath();
                        this.ctx.arc(x * 20 + 10, y * 20 + 10, 2, 0, Math.PI * 2);
                        this.ctx.fill();
                    }
                });
            });
        },

        drawPacman() {
            const cx = this.pacman.x + 10;
            const cy = this.pacman.y + 10;
            this.ctx.save();
            this.ctx.translate(cx, cy);

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
            this.ctx.arc(this.ghost.x + 10, this.ghost.y + 10, 8, Math.PI, 0);
            this.ctx.lineTo(this.ghost.x + 18, this.ghost.y + 18);
            this.ctx.lineTo(this.ghost.x + 2, this.ghost.y + 18);
            this.ctx.fill();

            this.ctx.fillStyle = 'white';
            this.ctx.beginPath();
            this.ctx.arc(this.ghost.x + 7, this.ghost.y + 8, 2, 0, Math.PI * 2);
            this.ctx.arc(this.ghost.x + 13, this.ghost.y + 8, 2, 0, Math.PI * 2);
            this.ctx.fill();
        }
    }
}

    </script>
</body>
</html>
