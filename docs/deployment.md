# Déploiement

## Environnements

- **local** : `docker compose up` (Vite dev + API + MySQL)
- **recette / production** : `docker compose -f docker-compose.prod.yml up -d --build`

Aucun secret ne doit être versionné. Utiliser `api/.env.example` et `mobile/.env.example` comme modèles.

## Prérequis prod

1. Copier et configurer les variables :
   - `api/.env` (ou variables dans `docker-compose.prod.yml`)
   - Clés JWT dans `api/config/jwt/` (voir `api/.env.example`)
2. `APP_ENV=prod`, `APP_DEBUG=0`
3. `APP_SECRET` et `JWT_PASSPHRASE` forts et uniques

## Lancement recette (Docker)

### Option A — script tout-en-un (recommandé)

```bash
./scripts/deploy.sh
# ou
make deploy
```

Le script :
1. Génère les clés JWT si absentes (`setup-jwt.sh`)
2. Crée un `.env` racine avec secrets aléatoires si absent
3. Lance `docker-compose.prod.yml`
4. Vérifie `/api/health`

### Option B — étape par étape

```bash
# 1. Clés JWT
./scripts/setup-jwt.sh
# ou: JWT_PASSPHRASE='...' ./scripts/setup-jwt.sh

# 2. Créer .env à la racine (APP_SECRET, JWT_PASSPHRASE, MYSQL_*)

# 3. Démarrer
docker compose -f docker-compose.prod.yml up -d --build
```

- Front : http://localhost (nginx sert le build statique)
- API : http://localhost/api/health
- Le front appelle `/api` (proxy nginx → conteneur `api`)

Variables utiles (`docker-compose.prod.yml`) :

| Variable | Défaut | Rôle |
|----------|--------|------|
| `FRONT_PORT` | `80` | Port exposé du front |
| `MYSQL_*` | voir compose | Base MySQL |
| `APP_SECRET` | — | Secret Symfony |
| `JWT_PASSPHRASE` | — | Passphrase des clés JWT |
| `CORS_ALLOW_ORIGIN` | `http://localhost` | Origine autorisée si front et API sont sur des hôtes différents |

## Build front seul (hors Docker)

```bash
cd mobile
cp .env.example .env.local   # optionnel
# VITE_API_URL=/api          # même origine
# VITE_API_URL=https://api.example.com
npm ci
npm run build
# Artefacts dans mobile/dist/
```

Servir `dist/` derrière nginx (ou CDN). Configurer le proxy `/api` ou une URL API absolue via `VITE_API_URL`.

## Migrations

- **Prod compose** : `doctrine:migrations:migrate` est lancé au démarrage du conteneur `api`.
- **Manuel** : `php bin/console doctrine:migrations:migrate --no-interaction`

## CI

Les tests tournent sur GitHub Actions (`.github/workflows/ci.yml`) : PHPUnit (API) + build + Vitest (mobile).
