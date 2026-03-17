# 🕹️ Pac-PHP | Game Engine com Laravel & Reverb

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![WebSockets](https://img.shields.io/badge/WebSockets-Reverb-blue?style=for-the-badge)

> "Quem disse que PHP só serve para formulários e CRUDs?" 

Este projeto é um **Pacman funcional** desenvolvido para demonstrar o poder do ecossistema PHP moderno. O objetivo principal é quebrar o preconceito de que o PHP é uma linguagem lenta ou limitada, utilizando tecnologias de ponta para entregar uma experiência em tempo real.

---

## 🚀 Diferenciais Técnicos (O que há sob o capô)

Para elevar o nível do portfólio, este projeto não utiliza uma engine de jogos pronta. Ele foi construído com uma arquitetura de **Servidor Autoritativo**:

* **Inteligência Artificial (Ghost AI):** O cálculo de movimento dos fantasmas (algoritmo de busca de caminho) é processado inteiramente no **Backend (PHP)**.
* **Comunicação em Tempo Real:** Utiliza o **Laravel Reverb** para transmitir o estado do jogo via WebSockets, garantindo latência mínima.
* **Estado Persistente:** O mapa, a pontuação e as colisões são validados no servidor usando o **Cache do Laravel**, impedindo trapaças no lado do cliente.
* **Renderização Fluida:** O frontend utiliza **HTML5 Canvas** e **Alpine.js** com um loop de animação de 60 FPS, sincronizado com os "ticks" do servidor.

---

## 🛠️ Tecnologias Utilizadas

- **Framework:** Laravel 12+
- **WebSocket Server:** Laravel Reverb
- **Frontend:** Alpine.js & HTML5 Canvas API
- **Tooling:** Vite, TailwindCSS
- **Testes:** Pest PHP

