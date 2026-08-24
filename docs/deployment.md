# Déploiement

L'application se lance avec Docker. Aucun secret ne doit être versionné.

Les environnements sont :

- **local** : `docker-compose.yml` (développement)
- **production** : `docker-compose.prod.yml` (VPS)

Les migrations de production ne sont **pas** exécutées au démarrage du conteneur. Elles se lancent manuellement après le premier `up` (base initialement vide) et après chaque déploiement qui ajoute une migration.

Ne jamais copier une base locale vers la production.

---

## Développement local

Prérequis : Docker, Docker Compose, Make (optionnel).

```
make up
```

Équivalent :

```
docker compose up -d --build
```

- API : http://localhost:8080/api/health → `{"status":"ok"}`
- Front Vite : http://localhost:5173

Le front de développement utilise `mobile/.env.development` (`VITE_API_URL` vide) et le proxy Vite `/api` vers le conteneur `api`. Caddy n'est pas utilisé en local.

Arrêt : `make down`  
Logs : `make logs`

Sans Docker, depuis `mobile/` : `npm run dev` et `npm run build` restent valides.

---

## Production (VPS)

Architecture :

```
Internet
    │
    ▼
Caddy (:80 / :443, Let's Encrypt)
    ├── petanque-analytics.com / www → frontend (nginx, fichiers Vite)
    └── api.petanque-analytics.com   → Symfony (PHP 8.4)
                                           └── mysql:3306 (réseau interne, non publié)
```

Le projet Compose de production s'appelle `petanque-stats-prod` : le volume MySQL `mysql_data` est distinct de celui du développement local.

### 1. Secrets

Sur le serveur, à la racine du dépôt cloné :

```
cp .env.prod.example .env
```

Renseigner les valeurs réelles (ne pas committer `.env`). Génération typique :

```
openssl rand -hex 32          # APP_SECRET
openssl rand -base64 32       # MYSQL_ROOT_PASSWORD / MYSQL_PASSWORD
```

Le runtime Symfony exige un fichier `.env` dans l'image. Le Dockerfile de production en crée un **sans secret** (`APP_ENV=prod`). Les valeurs réelles viennent du `.env` / des variables du compose au runtime.

Variables nécessaires (valeurs secrètes hors documentation) :

| Variable | Rôle |
| --- | --- |
| `APP_SECRET` | Secret Symfony |
| `DATABASE_URL` | Connexion Doctrine (`mysql://…@mysql:3306/…`) |
| `MYSQL_ROOT_PASSWORD` | Root MySQL |
| `MYSQL_DATABASE` | Nom de la base |
| `MYSQL_USER` | Utilisateur applicatif |
| `MYSQL_PASSWORD` | Mot de passe applicatif |
| `JWT_PASSPHRASE` | Passphrase de la clé privée JWT |
| `JWT_SECRET_KEY` / `JWT_PUBLIC_KEY` | Chemins des clés dans le conteneur (défauts du fichier d'exemple) |
| `CADDY_ACME_EMAIL` | Contact Let's Encrypt |
| `VITE_API_URL` | URL API injectée au **build** du frontend (`https://api.petanque-analytics.com/api`) |
| `DEFAULT_URI` | URI par défaut Symfony |
| `MAILER_DSN` | Inutilisé en V1 (`null://null`) |
| `MESSENGER_TRANSPORT_DSN` | Transport Messenger (défaut Doctrine) |
| `SYMFONY_TRUSTED_PROXIES` | Confiance dans Caddy (`REMOTE_ADDR`) |

Alternative : garder les secrets dans `.env.prod` et passer `--env-file .env.prod` à chaque commande `docker compose`.

### 2. Clés JWT

Les clés privées ne sont pas dans Git. Elles sont montées depuis `./jwt` vers `/app/config/jwt` (lecture seule).

Sur le VPS, **avant** le premier démarrage :

```
mkdir -p jwt
openssl genpkey -out jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in jwt/private.pem -pubout -out jwt/public.pem
chmod 600 jwt/private.pem
chmod 644 jwt/public.pem
```

La passphrase saisie pour `openssl` doit être la même que `JWT_PASSPHRASE` dans `.env`.

Sans chiffrer la clé (passphrase vide possible, moins souhaitable) :

```
openssl genpkey -out jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in jwt/private.pem -pubout -out jwt/public.pem
```

Le conteneur API refuse de démarrer si `jwt/private.pem` ou `jwt/public.pem` est absent.

Ne pas modifier la logique JWT applicative : seul le déploiement des fichiers de clés change.

### 3. Construire et démarrer

Depuis la racine du dépôt, avec `.env` renseigné et les clés JWT en place :

```
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

Première mise en service (build + start) :

```
docker compose -f docker-compose.prod.yml up -d --build
```

Caddy obtient automatiquement les certificats Let's Encrypt pour :

- `petanque-analytics.com`
- `www.petanque-analytics.com`
- `api.petanque-analytics.com`

Le HTTP est redirigé vers HTTPS. Les ports publiés sont uniquement **80** et **443**. MySQL, PHP et nginx interne ne sont pas exposés.

Le DNS doit déjà pointer vers le VPS, et les ports 80/443 doivent être ouverts (UFW). Cette documentation ne couvre pas la configuration du serveur.

### 4. Migrations

Base de production initialement vide. Après que MySQL et l'API sont sains :

```
docker compose -f docker-compose.prod.yml exec api php bin/console doctrine:migrations:migrate --no-interaction
```

Répéter cette commande après un déploiement qui ajoute des migrations.

### 5. Logs et diagnostic

```
docker compose -f docker-compose.prod.yml logs -f
docker compose -f docker-compose.prod.yml logs -f api
docker compose -f docker-compose.prod.yml ps
```

Contrôle API (depuis le VPS ou l'extérieur une fois HTTPS actif) :

```
curl -fsS https://api.petanque-analytics.com/api/health
```

### 6. Arrêt

```
docker compose -f docker-compose.prod.yml down
```

Ne pas ajouter `-v` : cela supprimerait le volume `mysql_data` (données perdues).

---

## Application Android (Capacitor)

Le frontend Vue existant (`mobile/`) est empaqueté dans une application Android. Il n'y a pas de second frontend.

### Installation

Depuis `mobile/`, après `npm install` :

- `@capacitor/core`, `@capacitor/cli` et `@capacitor/android` sont déjà déclarés dans `package.json`.

### Build web

```
cd mobile
npm run build
```

Ce build alimente le navigateur, la PWA (lorsqu'elle est configurée) et Capacitor. `mobile/.env.production` définit :

```
VITE_API_URL=https://api.petanque-analytics.com/api
```

### Synchronisation Android

```
npx cap sync android
```

Équivalent : `npm run build:android` (build web + sync).

### Ouverture Android Studio

```
npx cap open android
```

Équivalent : `npm run android:open`.

### Lancement sur un appareil physique

1. Relier le téléphone en USB, activer le débogage USB, vérifier avec `adb devices`.
2. Dans Android Studio : Gradle JDK **17 ou 21** (Capacitor 8 / AGP 8.13). Éviter le JBR 25 si la compilation échoue.
3. Sélectionner l'appareil, puis Run.
4. Le package installé est `com.petanquestats.app` (Pétanque Stats).
5. Release Play : `npm run android:bundle` (voir `docs/google-play-android.md`).

Signature, AAB et Data Safety : `docs/google-play-android.md`, `docs/google-play-data-safety.md`.

### Environnement API (Android)

Android utilise le même `npm run build` que la production web, donc `VITE_API_URL` de `mobile/.env.production`.

Pour pointer vers une autre API publique, modifier uniquement cette variable, relancer `npm run build` puis `npx cap sync android`. Ne pas changer le code métier ni Capacitor pour cela.

Ne jamais mettre `http://localhost:8080` dans un build Android.

En développement navigateur, laisser `VITE_API_URL` vide (`mobile/.env.development`) pour conserver le proxy Vite vers l'API Docker.
