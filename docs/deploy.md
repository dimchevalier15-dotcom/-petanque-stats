Utilisation du script de deploy

./scripts/deploy.sh
./scripts/deploy.sh --no-build
./scripts/deploy.sh --pull-only
./scripts/deploy.sh --help

Après le `up` et une fois le service `api` healthy, le script vérifie
`doctrine:migrations:up-to-date` et, s’il reste des migrations, exécute
`php bin/console doctrine:migrations:migrate --no-interaction` dans le
conteneur api. `--pull-only` ne joue pas les migrations.