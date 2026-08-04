# Pétanque Analytics

# 05 - Architecture

**Version :** 0.1

**Statut :** Validé

---

# Philosophie

L'architecture doit rester simple.

Le projet est développé par une seule personne.

Le métier est prioritaire sur la technique.

Toute complexité non indispensable doit être évitée.

---

# Monorepository

Le projet est organisé sous la forme d'un monorepo.

```
petanque-analytics/

    api/

    mobile/

    docs/

    docker/

    docker-compose.yml

    Makefile

    README.md
```

Les deux applications sont indépendantes.

Elles sont simplement versionnées ensemble.

---

# Stack

## Backend

- PHP 8.4
- Symfony 7
- Doctrine ORM
- MySQL 8
- JWT
- PHPUnit
- PHPStan

---

## Frontend

- Vue 3
- TypeScript
- Composition API
- Vite
- Pinia
- Vue Router
- Axios
- PrimeVue
- Capacitor
- Vitest
- Biome

---

# Internationalisation

Le projet est nativement multilingue.

Les langues prévues sont :

- Français
- Anglais
- Slovaque

Règles :

- aucun texte en dur dans l'interface ;
- toutes les chaînes passent par le système i18n ;
- le code est écrit en anglais ;
- la documentation est écrite en français.

---

# Backend

Architecture volontairement simple.

```
Controller

↓

DTO

↓

Service

↓

Repository

↓

Entity
```

Les Controllers :

- ne contiennent aucune logique métier.

Les Services :

- contiennent toute la logique métier.

Les Repositories :

- gèrent uniquement la persistance.

Les Entities :

- représentent le métier.

---

# Frontend

Architecture souhaitée

```
src/

assets/

components/

views/

router/

stores/

services/

types/

composables/

utils/

i18n/
```

---

# MatchDraft

Le MatchDraft est le cœur du Front.

Il représente entièrement une partie en cours.

Il contient notamment :

- les joueurs ;
- les équipes ;
- les mènes ;
- les actions ;
- le contexte.

Le MatchDraft constitue la source de vérité du Front.

---

# Sauvegarde

Pendant toute la partie :

Le MatchDraft est sauvegardé localement.

Le backend ne reçoit jamais une partie en cours.

---

# Communication API

Une partie est envoyée uniquement lorsqu'elle est terminée.

Le backend reçoit un unique :

CreateMatchDTO

Le backend :

- valide ;
- persiste ;
- retourne le Match créé.

---

# Docker

Services :

- api
- mobile
- mysql

Le projet doit démarrer avec :

docker compose up

---

# Makefile

Le projet fournit notamment :

- make up
- make down
- make build
- make logs
- make api
- make mobile
- make lint
- make test
- make fix
- make sync

---

# TypeScript

Configuration stricte.

Le type "any" est interdit.

Les exceptions doivent être justifiées.

---

# Qualité

Frontend

- Biome
- Vitest

Backend

- PHPUnit
- PHPStan

Le code doit toujours rester formaté automatiquement.

---

# PrimeVue

Importer uniquement les composants réellement utilisés.

Éviter les imports globaux.

---

# Capacitor

Capacitor est utilisé uniquement comme conteneur mobile.

Le développement reste celui d'une application Vue classique.

L'objectif est de conserver un maximum de code web.

---

# Architecture Front

Les composants :

- affichent.

Ils ne calculent pas.

Les calculs métier appartiennent :

- aux composables ;
- aux services.

Les composants ne communiquent jamais directement avec Axios.

---

# Architecture Back

Les Controllers :

- lisent la Request ;
- créent le DTO ;
- appellent un Service ;
- retournent la Response.

Ils ne contiennent aucune logique métier.

---

# Persistance

Les statistiques ne sont jamais stockées.

Toutes les statistiques sont calculées à partir des Actions.

---

# Ce qui est volontairement absent

- CQRS
- Event Sourcing
- Messenger
- API Platform
- GraphQL
- Microservices
- Redis
- RabbitMQ
- Elasticsearch

Ces technologies ne répondent à aucun besoin de la V1.

---

# Objectif

Le projet doit rester compréhensible par un développeur en moins d'une heure.

La simplicité est une fonctionnalité.