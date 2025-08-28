# Pixel BR – Starter Kit (Mercado Afiliado)

Este kit contém um protótipo completo: **Pixel BR (client-side)**, **coletor (Node/Express)** e **stubs do CAPI Bridge** para Meta/Google/TikTok.
Inclui **Dockerfile** e **docker-compose.yml** para subir o coletor rapidamente.

> ⚠️ Os bridges são *stubs* (não enviam às APIs reais). Preencha `.env` e implemente as chamadas HTTP para produção.

## Subir com Docker
```bash
cp .env.example .env
docker compose up --build
# o coletor sobe em http://localhost:8080
```

## Subir localmente (Node)
```bash
cd server/node
cp ../../.env.example .env
npm install
npm run dev
```

## Testes
```bash
cd server/node
node tests/test_runner.js
```

## Demo do Pixel
Abra `client/demo.html` no navegador (o script já aponta para `http://localhost:8080/collect`).

## Estrutura
```
pixel_br_starter_kit/
├─ README.md
├─ .env.example
├─ docker-compose.yml
├─ client/
│  ├─ pixel_br.js
│  └─ demo.html
├─ server/
│  ├─ node/
│  │  ├─ package.json
│  │  ├─ server.js
│  │  ├─ bridges/
│  │  │  ├─ meta.js
│  │  │  ├─ google.js
│  │  │  └─ tiktok.js
│  │  ├─ schema/
│  │  │  └─ event.schema.json
│  │  ├─ utils/
│  │  │  ├─ hash.js
│  │  │  ├─ consent.js
│  │  │  └─ dedupe.js
│  │  └─ tests/
│  │     ├─ sample_events.json
│  │     └─ test_runner.js
│  └─ php-laravel/
│     ├─ README_LARAVEL.md
│     ├─ PixelEventController.php
│     └─ EventSchema.json
├─ scripts/
│  ├─ generate_snippet.js
│  └─ hash_cli.js
└─ LICENSE
```
