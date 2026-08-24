# Pétanque Analytics

Bootstrap technique du projet. Voir le dossier `docs/` pour la source de vérité.

## Prérequis
- Docker et Docker Compose récents
- Make (optionnel mais recommandé)
- Node.js 22+ (si vous souhaitez lancer le front / Capacitor 8 sans Docker)

## Architecture (monorepo)
```
petanque-analytics/
│
├── api/
├── mobile/
├── docs/
├── docker/ (Caddyfile production)
├── docker-compose.yml
├── docker-compose.prod.yml
├── Makefile
└── README.md
```

## Stack
Backend
- PHP 8.4
- Symfony 7
- Doctrine ORM (MySQL 8)
- Lexik JWT
- PHPUnit, PHPStan

Frontend (mobile/web)
- Vue 3 + Vite + TypeScript (strict)
- Composition API, Pinia, Vue Router, Axios, PrimeVue
- Capacitor (Android : `com.petanquestats.app`)
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
- make up: build + start des services
- make down: arrêt
- make build: rebuild sans cache
- make logs: suivre les logs
- make api: shell dans le conteneur API
- make mobile: shell dans le conteneur mobile
- make lint: Biome (frontend)
- make fix: Biome avec corrections (frontend)
- make test: Vitest (frontend) + PHPUnit (API)
- make sync: cap sync (conteneur mobile)
- make prod-up / prod-down / prod-logs / prod-migrate : commandes Docker de production (voir `docs/deployment.md`)

## Production

Configuration séparée : `docker-compose.prod.yml` (Caddy, frontend statique, API `APP_ENV=prod`, MySQL non exposé).

Procédure complète (secrets, JWT, migrations) : `docs/deployment.md`.

Ne pas utiliser ce fichier pour le développement local.

## Notes techniques
- JWT: Lexik JWT. En local, clés dans `api/config/jwt` (non versionnées). En production, clés dans `./jwt` sur le VPS (voir `docs/deployment.md`).
- Doctrine: configuré pour MySQL via `DATABASE_URL`.
- Front production / Android: `VITE_API_URL=https://api.petanque-analytics.com/api` (`mobile/.env.production`).
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

## Application Android (Capacitor)

Le frontend `mobile/` est la source unique (navigateur, PWA, Android).

```
cd mobile
npm install
npm run build
npx cap sync android
npx cap open android
```

Puis lancer **Pétanque Stats** depuis Android Studio sur un appareil USB (`adb devices`).

Dans Android Studio, choisir un **Gradle JDK 17 ou 21** (Capacitor 8 / Android API 36). Publication Play : `docs/google-play-android.md`.

L'API Android est celle de production (`VITE_API_URL` dans `mobile/.env.production`, actuellement `https://api.petanque-analytics.com/api`). Le mode `npm run dev` continue d'utiliser le proxy `/api` vers l'API locale.

Détails : `docs/deployment.md`.

## TODO
- Compléter la CI (non incluse ici).
- Politique de confidentialité publique et suppression de compte avant publication Play Store (`docs/google-play-android.md`).