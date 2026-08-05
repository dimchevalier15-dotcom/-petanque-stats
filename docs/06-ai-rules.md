# Pétanque Analytics

# 06 - AI Rules

**Version :** 0.1

**Statut :** Validé

---

Le rôle de l'IA est de réduire la charge cognitive du développeur, pas de démontrer ses connaissances.

# Objectif

Ce document définit les règles que toute IA intervenant sur le projet doit respecter.

Ces règles sont prioritaires sur toute proposition technique.

---

# Philosophie

L'objectif n'est pas d'écrire le plus de code.

L'objectif est d'écrire le bon code.

La simplicité est une fonctionnalité.

---

# Source de vérité

Les documents du dossier `docs/` sont la référence officielle.

L'IA ne doit jamais inventer une règle métier absente de cette documentation.

En cas de doute :

- laisser un TODO ;
- ou demander une clarification.

Ne jamais faire d'hypothèse métier.

---

# Simplicité

Toujours privilégier :

- la solution la plus simple ;
- la plus lisible ;
- la plus maintenable.

Ne jamais complexifier la V1 pour anticiper une V2.

---

# Métier

Le métier est prioritaire.

La technique ne doit jamais imposer une règle métier.

Si une solution technique entre en conflit avec le métier :

le métier gagne.

---

# Architecture

Respecter l'architecture définie dans :

05-architecture.md

Ne pas introduire de nouveaux patterns sans validation.

---

# Technologies

Ne jamais ajouter une technologie qui n'a pas été validée.

Exemples :

Ne pas ajouter :

- Redis
- RabbitMQ
- GraphQL
- CQRS
- Event Sourcing
- API Platform
- Elasticsearch

sans demande explicite.

---

# Frontend

Les composants Vue doivent rester simples.

Ils affichent des données.

Ils ne contiennent pas de logique métier.

Toute logique métier appartient :

- aux composables ;
- aux services ;
- aux stores si nécessaire.

---

# Backend

Les Controllers :

- lisent la Request ;
- créent le DTO ;
- appellent un Service.

Ils ne contiennent aucune logique métier.

---

# TypeScript

TypeScript est obligatoire.

Configuration stricte.

Le type `any` est interdit.

Toute exception doit être explicitement justifiée.

---

# Documentation

Toute décision importante doit être documentée.

Le code doit rester cohérent avec les fichiers du dossier `docs/`.

Si le code et la documentation divergent :

la documentation est considérée comme correcte jusqu'à validation contraire.

---

# Internationalisation

Le projet est multilingue.

Ne jamais écrire de texte directement dans un composant.

Toutes les chaînes passent par le système i18n.

Le code est écrit en anglais.

La documentation reste en français.

---

# Expérience utilisateur

Toujours chercher à réduire :

- le nombre de clics ;
- le nombre d'écrans ;
- le temps de saisie.

Toute proposition qui ajoute des interactions doit être justifiée.

---

# Développement

Développer une fonctionnalité complète avant de passer à la suivante.

Ne jamais commencer plusieurs fonctionnalités en parallèle.

Le projet doit rester compilable après chaque étape.

---

# Qualité

Privilégier :

- des fonctions courtes ;
- des classes simples ;
- des noms explicites.

Le code est écrit pour être relu plusieurs années plus tard.

---

# Refactoring

Améliorer le code existant plutôt que le remplacer entièrement.

Limiter les modifications au périmètre concerné.

Éviter les refactorings massifs sans nécessité.

---

# Dépendances

Toute nouvelle dépendance doit répondre à un besoin réel.

Avant d'ajouter une bibliothèque :

vérifier si l'existant permet déjà de répondre au besoin.

---

# Communication

Si une demande semble contradictoire avec la documentation :

ne pas choisir arbitrairement.

Signaler la contradiction.

Proposer une ou plusieurs solutions.

Attendre une validation.

---

# Priorité

Toujours respecter cet ordre.

1. Les règles métier.
2. L'expérience utilisateur.
3. La simplicité.
4. Les performances.
5. L'élégance technique.

Une solution plus élégante techniquement ne doit jamais dégrader les trois premiers points.

---

# Principe final

L'IA est un assistant de développement.

Elle ne décide jamais du produit.

Elle implémente les décisions validées dans la documentation.

En cas d'incertitude :

elle pose une question plutôt que de faire une hypothèse.

## Simplicité avant modernité

Ne jamais introduire une technologie ou une abstraction non demandée.

Exemples :

- UUID
- ULID
- Event Sourcing
- CQRS
- Bus de messages
- Value Objects
- Specifications
- Architecture hexagonale
- Event Dispatcher
- Design patterns avancés

La V1 privilégie systématiquement la solution la plus simple répondant au besoin métier.


## Organisation des types TypeScript

Ne jamais déclarer un type ou une interface dans un composant Vue lorsqu'il représente une donnée métier ou un contrat d'API.

Toujours créer un fichier dédié.

Organisation :

- src/models : modèles métier
- src/dto : contrats d'API (Request / Response)

Les composants importent toujours ces types.

Objectif :

- un modèle = une définition ;
- aucune duplication ;
- aucun type métier déclaré localement dans un composant.


# Architecture Backend

## Principe

Le backend suit une architecture simple.

```
HTTP
    ↓
Controller
    ↓
Service
    ↓
Repository
    ↓
Database
```

Le Controller ne contient jamais de logique métier.

---

## Controllers

Le Controller a une seule responsabilité.

Il doit uniquement :

- recevoir la requête HTTP ;
- désérialiser le DTO d'entrée ;
- déclencher la validation Symfony ;
- appeler un Service ;
- retourner un DTO de réponse.

Le Controller ne doit jamais :

- parser du JSON manuellement ;
- construire une Entity ;
- appeler Doctrine directement ;
- écrire une requête DQL ;
- contenir des règles métier.

Un Controller doit rester très court.

Objectif :

moins de 30 lignes par méthode.

---

## DTO

Tous les endpoints utilisent des DTO.

Organisation :

```
src/
    DTO/
        Request/
        Response/
```

Exemple :

```
CreatePlayerRequest
CreatePlayerResponse

LoginRequest
LoginResponse

RegisterRequest
RegisterResponse
```

Les Controllers ne manipulent jamais directement les Entities.

---

## Services

Toute la logique métier est placée dans un Service.

Exemple :

```
PlayerService

AuthService

MatchService
```

Les Services :

- appliquent les règles métier ;
- créent les Entities ;
- utilisent les Repositories.

Ils ne connaissent jamais HTTP.

Ils ne retournent jamais de JsonResponse.

---

## Repositories

Les requêtes Doctrine sont placées dans les Repository.

Les Controllers et Services n'utilisent jamais QueryBuilder directement.

Les méthodes doivent être explicites.

Exemple :

```
search(string $query): array

findByEmail(string $email): ?User
```

---

## Validation

Toujours utiliser le composant Validator de Symfony.

Ne jamais construire un tableau d'erreurs manuellement.

Les contraintes sont portées par les DTO.

Exemple :

- NotBlank
- Length
- Email

---

## Serializer

Toujours utiliser le Serializer Symfony.

Ne jamais utiliser :

json_decode()

Ne jamais construire les réponses JSON sous forme de tableaux.

Les DTO de réponse sont sérialisés automatiquement.

---

## Typage

Strict types obligatoires.

Ne jamais utiliser :

- mixed
- array<string,mixed>

Créer un DTO dès qu'une structure est échangée.

---

## Objectif

Le code doit être lisible.

Chaque classe possède une responsabilité unique.

Le backend doit rester simple.

La simplicité est toujours préférée à une implémentation "intelligente".