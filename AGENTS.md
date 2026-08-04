# AGENTS.md

# Pétanque Stats

## Mission

Ce dépôt est un monorepo dédié au développement d'une application moderne de gestion et d'analyse de compétitions de pétanque.

L'objectif principal est de produire un logiciel robuste, maintenable, documenté et évolutif.

L'IA est un partenaire de développement. Elle ne doit jamais sacrifier la qualité du code au profit de la vitesse.

---

# Philosophie générale

Toujours privilégier :

- la lisibilité
- la simplicité
- le métier
- le typage fort
- les tests
- la maintenabilité

Ne jamais ajouter de complexité inutile.

Chaque décision technique doit pouvoir être justifiée.

---

# Architecture

Le dépôt est un monorepo.

```
apps/
    api/
    mobile/

packages/
    shared/
    design/

docs/
docker/
```

Le backend est indépendant du mobile.

Le mobile ne contient jamais de logique métier.

Toute la logique métier vit dans le backend.

---

# Stack technique

Backend

- PHP 8.4
- Symfony 7
- API Platform
- Doctrine ORM
- PostgreSQL
- PHPUnit

Frontend Mobile

- Flutter
- Dart
- Riverpod
- GoRouter

Infrastructure

- Docker
- Docker Compose

---

# Objectif métier

Le métier est prioritaire.

Avant de développer une fonctionnalité :

1. comprendre le besoin
2. comprendre les règles métier
3. seulement ensuite produire du code

Ne jamais inventer une règle métier.

En cas d'ambiguïté :

poser une question.

---

# Style de code

Toujours produire :

- du code fortement typé
- des classes courtes
- des méthodes courtes
- des noms explicites
- aucune variable ambiguë

Éviter :

- les commentaires inutiles
- les méthodes de plusieurs centaines de lignes
- les classes "God Object"

---

# SOLID

Respecter les principes SOLID.

Privilégier la composition à l'héritage.

---

# Clean Code

Favoriser :

- une responsabilité par classe
- une responsabilité par méthode

Les méthodes privées doivent rester courtes.

Limiter les effets de bord.

---

# Domain Driven Design

Le projet suit un DDD léger.

Ne pas créer une architecture DDD complexe.

Organisation recommandée :

```
Domain/
Application/
Infrastructure/
UI/
```

Le domaine ne dépend jamais de Symfony.

---

# Symfony

Utiliser :

- autowiring
- autoconfiguration
- readonly lorsque pertinent
- constructor promotion
- attributes PHP

Ne jamais utiliser :

- services statiques
- container injection
- paramètres globaux inutiles

---

# API

L'API est REST.

Toujours :

- utiliser les bons codes HTTP
- produire des erreurs explicites
- documenter automatiquement via OpenAPI

---

# Doctrine

Utiliser Doctrine proprement.

Éviter :

- les requêtes N+1
- les repositories énormes
- les entités anémiques

Préférer :

- QueryBuilder
- Value Objects
- Enum PHP

---

# Base de données

PostgreSQL.

Utiliser :

- UUID
- contraintes
- index

Les migrations doivent être propres.

Ne jamais modifier une migration déjà exécutée.

---

# Flutter

Le mobile est une couche de présentation.

Aucune logique métier complexe.

Architecture recommandée :

```
features/
core/
shared/
```

Utiliser Riverpod.

Ne jamais utiliser setState pour gérer l'application entière.

---

# Tests

Toute logique métier importante doit être testée.

Priorité :

- tests unitaires
- puis tests fonctionnels

Ne jamais générer des tests artificiels uniquement pour augmenter la couverture.

---

# Performance

Toujours réfléchir aux performances.

Éviter :

- les allocations inutiles
- les boucles coûteuses
- les requêtes SQL multiples

---

# Sécurité

Ne jamais :

- stocker un mot de passe en clair
- exposer une information sensible
- faire confiance aux données utilisateur

Toujours valider les entrées.

---

# Logs

Les logs doivent être utiles.

Pas de spam.

Les erreurs doivent être compréhensibles.

---

# Git

Faire de petits commits.

Les messages doivent être explicites.

Exemple :

```
Add tournament statistics service

Fix ranking calculation

Implement player search endpoint
```

---

# Documentation

Toute décision importante doit être documentée.

Créer un ADR dans :

```
docs/decisions/
```

si une décision d'architecture importante est prise.

---

# IA

L'IA ne doit jamais modifier massivement le projet sans justification.

Avant une refactorisation importante :

expliquer :

- pourquoi
- les avantages
- les risques

---

# Si une information manque

Toujours demander.

Ne jamais inventer.

---

# Règle d'or

Le métier est plus important que la technique.

La simplicité est plus importante que l'élégance.

Un code compréhensible est préférable à un code "intelligent".

Chaque ligne de code doit faciliter le travail du développeur qui la relira dans deux ans.