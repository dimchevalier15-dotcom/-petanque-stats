# Sauvegardes MySQL (production)

Système de backup **local** de la base MySQL de production. Il n'est **pas** installé dans cron automatiquement.

Le volume Docker `mysql_data` n'est jamais modifié, supprimé ni recréé. Les scripts ne font pas `DROP DATABASE` / `CREATE DATABASE`.

## Prérequis

- Projet déployé sous `/opt/petanque-stats` (ou toute racine contenant `docker-compose.prod.yml`)
- Production lancée avec `docker compose -f docker-compose.prod.yml`
- Service Compose : `mysql` (image `mysql:8.0`, projet `petanque-stats-prod`)
- Variables `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` injectées dans le conteneur depuis le `.env` de production (jamais hardcodées dans les scripts)
- L'utilisateur qui lance le script doit pouvoir exécuter `docker compose`

MySQL n'a pas besoin d'être publié sur un port hôte : le dump passe par `docker compose exec`.

`docker-compose.prod.yml` n'a pas à être modifié pour ce système.

## Backup manuel

Depuis n'importe quel répertoire :

```
/opt/petanque-stats/scripts/backup-db.sh
```

Équivalent depuis la racine du projet :

```
./scripts/backup-db.sh
```

Le script :

1. localise la racine du projet (dossier parent de `scripts/`)
2. vérifie que le service `mysql` tourne et répond à `mysqladmin ping`
3. crée `backups/` si besoin
4. exécute `mysqldump` **dans** le conteneur (`--single-transaction` pour InnoDB, structure + données, routines / triggers / events)
5. compresse en gzip
6. vérifie que le fichier n'est pas vide, que gzip est valide, et que le SQL contient un dump MySQL complet
7. **ensuite seulement** supprime les dumps locaux de plus de 14 jours

En cas d'échec, le code de sortie est non nul. Aucun ancien backup n'est alors supprimé.

## Emplacement des fichiers

| Élément | Chemin |
| --- | --- |
| Dumps | `/opt/petanque-stats/backups/petanque-YYYY-MM-DD_HH-MM-SS.sql.gz` |
| Journal cron | `/opt/petanque-stats/backups/backup.log` |
| Scripts | `/opt/petanque-stats/scripts/backup-db.sh`, `restore-db.sh` |

Exemple de fichier généré :

```
/opt/petanque-stats/backups/petanque-2026-08-26_03-00-00.sql.gz
```

Permissions : dossier et dumps créés avec umask `077` (lisibles par le propriétaire uniquement).

Le dossier `backups/` est dans `.gitignore` : ne jamais committer de dumps.

## Rétention

14 jours, **uniquement après un backup nouvellement validé**. Les fichiers qui ne matchent pas le motif `petanque-YYYY-MM-DD_HH-MM-SS.sql.gz` ne sont pas supprimés. `backup.log` n'est pas concerné.

## Vérifier un backup (sans restaurer)

```
./scripts/backup-db.sh --verify /opt/petanque-stats/backups/petanque-2026-08-26_03-00-00.sql.gz
```

Contrôles effectués :

- fichier non vide
- archive gzip intègre (`gzip -t`)
- contenu SQL : en-tête `MySQL dump`, au moins un `CREATE TABLE` / `DROP TABLE`, pied `Dump completed`

Équivalent manuel :

```
test -s /opt/petanque-stats/backups/petanque-2026-08-26_03-00-00.sql.gz
gzip -t /opt/petanque-stats/backups/petanque-2026-08-26_03-00-00.sql.gz
gzip -dc /opt/petanque-stats/backups/petanque-2026-08-26_03-00-00.sql.gz | tail -n 5
```

## Restauration (manuelle, destructive)

Le script `scripts/restore-db.sh` **n'est jamais lancé automatiquement**. Il refuse cron, pipes, et toute option `--yes` / `--force`.

```
./scripts/restore-db.sh /opt/petanque-stats/backups/petanque-2026-08-26_03-00-00.sql.gz
```

Il affiche un avertissement (écrasement des données de la base existante) et n'importe le dump que si vous tapez exactement `RESTORE`.

Recommandé avant l'import :

```
docker compose -f docker-compose.prod.yml stop api
```

Puis après succès :

```
docker compose -f docker-compose.prod.yml start api
```

Le volume `mysql_data` n'est pas supprimé. La base `petanque` n'est pas droppée : les tables sont remplacées par le contenu du dump.

## Cron quotidien (03:00) — à installer manuellement

Ne pas installer sans validation. Ligne crontab (utilisateur ayant accès à Docker, souvent `root`) :

```
0 3 * * * mkdir -p /opt/petanque-stats/backups && PATH=/usr/local/bin:/usr/bin:/bin /opt/petanque-stats/scripts/backup-db.sh >> /opt/petanque-stats/backups/backup.log 2>&1
```

`mkdir -p` est nécessaire : la redirection vers `backup.log` échoue si `backups/` n'existe pas encore.

Vérifier après installation :

```
crontab -l
tail -n 50 /opt/petanque-stats/backups/backup.log
```

Fuseau : celui de l'horloge système du VPS.

## Procédure de test recommandée

Ne pas tester la restauration sur la base de production « pour voir ».

1. **Syntaxe des scripts** (sans Docker) :
   ```
   bash -n /opt/petanque-stats/scripts/backup-db.sh
   bash -n /opt/petanque-stats/scripts/restore-db.sh
   ```
2. **Backup manuel** sur le VPS, hors heures de pointe :
   ```
   /opt/petanque-stats/scripts/backup-db.sh
   ```
3. **Vérifier** le fichier généré avec `--verify` (ci-dessus).
4. **Contrôler les logs** : pas de mot de passe MySQL, présence de `[OK]`.
5. **Restauration** : uniquement sur une copie de la stack (autre VPS / compose de test) ou après incident réel, jamais « à blanc » sur la prod.

## Limites

- Les dumps restent **sur le même disque** que la production : une panne disque ou un `rm` du serveur perd l'application **et** les backups. Prévoir une copie hors serveur (S3, autre machine) si besoin.
- Pas de chiffrement applicatif des dumps (seulement les droits Unix 600).
- Pas de restauration unitaire (une table) ni de PITR (point-in-time) : snapshot `mysqldump` uniquement.
- Charge I/O pendant le dump ; `--single-transaction` évite les locks de tables InnoDB mais n'est pas un freeze LVM.
- Le cron n'envoie pas d'alerte mail/Slack si le backup échoue : surveiller `backup.log` (ou ajouter un mail cron).
- `MYSQL_PWD` est utilisé **à l'intérieur** du conteneur pour ne pas passer `-p...` en clair ; il peut apparaître dans `/proc` du processus mysqldump le temps du dump.
- Ce n'est pas un backup du volume Docker brut (`mysql_data`) ni des fichiers JWT / `.env`.
