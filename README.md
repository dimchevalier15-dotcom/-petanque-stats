# Pétanque Analytics

Bootstrap technique du projet. Voir le dossier `docs/` pour la source de vérité.

## Prérequis
- Docker et Docker Compose récents
- Make (optionnel mais recommandé)
- Node.js 20+ (si vous souhaitez lancer le front sans Docker)

## Architecture (monorepo)
```
petanque-analytics/
│
├── api/
├── mobile/
├── docs/
├── docker/ (réservé)
├── docker-compose.yml
├── Makefile
└── README.md
```

## Stack
Backend
- PHP 8.4
- Symfony 7
- Doctrine ORM (MySQL 8)
- Lexik JWT (auth sur les routes match)
- PHPUnit, PHPStan

Frontend (mobile/web)
- Vue 3 + Vite + TypeScript (strict)
- Composition API, Pinia, Vue Router, Axios, PrimeVue
- Capacitor (ouverture Android Studio possible)
- Vitest, Biome

## Lancement rapide
1. Démarrer les services
```
make up
```
- API: http://localhost:8080/api/health → {"status":"ok"}
- Front: http://localhost:5173

2. Logs
```
make logs
```

3. Arrêt
```
make down
```

## Commandes Make utiles
- make up: build + start des services (dev)
- make up-prod: stack recette/prod (`docker-compose.prod.yml`)
- make down: arrêt
- make build: rebuild sans cache
- make logs: suivre les logs
- make api: shell dans le conteneur API
- make mobile: shell dans le conteneur mobile
- make lint: Biome (frontend)
- make fix: Biome avec corrections (frontend)
- make test: Vitest (frontend) + PHPUnit (API)
- make sync: docker compose pull

## Notes techniques
- JWT: Lexik activé (`security.yaml`), clés dans `api/config/jwt/` (voir `api/.env.example`).
- API URL front: `VITE_API_URL` (défaut `/api`) — voir `mobile/.env.example` et `docs/deployment.md`.
- Doctrine: configuré pour MySQL via DATABASE_URL (voir docker-compose.yml).
- i18n: fr, en, sk. Aucun texte en dur dans les composants.
- TypeScript: mode strict, any interdit.

## Structure
Backend (api/src)
- Controller/
- DTO/
- Entity/
- Enum/
- Repository/
- Service/
- Security/

Frontend (mobile/src)
- assets/, components/, views/, layouts/
- router/, stores/, services/
- types/, composables/, utils/
- i18n/

## Tests & Qualité
- Front: Vitest, Biome
- Back: PHPUnit, PHPStan (`composer phpstan` dans le conteneur API)
- CI: GitHub Actions (`.github/workflows/ci.yml`) — PHPUnit + build front + Vitest

## Configuration
- `api/.env.example` — variables Symfony, JWT, CORS, base de données
- `mobile/.env.example` — `VITE_API_URL` pour le build production
- `scripts/setup-jwt.sh` — génération des clés JWT
- `scripts/deploy.sh` — déploiement prod complet (JWT + `.env` + Docker)
- Déploiement: `docs/deployment.md`