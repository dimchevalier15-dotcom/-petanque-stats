# Pétanque Analytics

# 04 - Modèle de données

**Version :** 0.1

**Statut :** Validé

---

# Philosophie

Le modèle métier constitue la source de vérité du projet.

Le modèle Doctrine devra respecter ce document.

Les statistiques ne sont jamais stockées.

Elles sont toujours calculées à partir des Actions.

---

# Vue d'ensemble

```

User
│
└── Player

↓

Match

├── Team

├── MatchPlayer

├── End

└── Action

↓

Venue

↓

Club

```

---

# User

Représente un compte utilisateur.

## Attributs

- id
- email
- password
- role
- premiumUntil
- settings
- createdAt

## Relations

- possède un Player

---

# Player

Représente une personne.

Le Player est le cœur du projet.

## Attributs

- id
- firstname
- lastname
- nickname
- country
- visibility
- createdAt
- updatedAt

## Relations

- appartient éventuellement à un Club
- peut être revendiqué par un User
- participe à plusieurs Matchs

---

# Club

Représente un club de pétanque.

Un Player peut appartenir à un Club.

Le rattachement est optionnel et peut être renseigné dans le profil, à l'inscription ou lors de la création d'un joueur.

## Attributs

- id
- name
- description
- country

## Relations

- appartient à un Country
- contient des Players

---

# Country

Liste de pays de référence.

Utilisée pour lier un Club à un pays.

## Attributs

- id
- isoCode
- name

---

# Venue

Lieu où se déroule un Match.

## Attributs

- id
- name
- city
- country

---

# Match

Représente une partie.

## Attributs

- id
- playedAt
- format
- notationMode
- status
- matchType
- competitionName
- competitionStage
- note

## Relations

- creator
- venue
- teams
- matchPlayers
- ends

---

# Team

Une équipe d'un Match.

Un Match possède toujours exactement deux Teams.

## Attributs

- id
- order

---

# MatchPlayer

Participation d'un Player à un Match.

## Attributs

- id
- startingRole
- tracked

## Relations

- Match
- Team
- Player

---

# End

Une mène.

## Attributs

- id
- number
- winnerTeam
- points

## Relations

- Match
- Actions

---

# Action

Une boule jouée.

## Attributs

- id
- type
- score
- distance
- order
- createdAt

## Relations

- Player
- End

---

# Enums

## MatchFormat

- HEAD_TO_HEAD
- DOUBLETTE
- TRIPLETTE

---

## MatchType

- TRAINING
- FRIENDLY
- COMPETITION

---

## NotationMode

- STANDARD
- SIMPLE

---

## Role

- POINTER
- MIDDLE
- SHOOTER

---

## ActionType

- POINT
- SHOOT

---

## CompetitionStage

- GROUP
- SWISS
- TOP_64
- TOP_32
- TOP_16
- QUARTER_FINAL
- SEMI_FINAL
- FINAL
- OTHER

---

## UserRole

- SIMPLE_PLAYER
- MASTER

L'administration (impersonate, compétitions) est réservée au rôle MASTER.

---

## Visibility

- PRIVATE
- FRIENDS
- PUBLIC

---

# Règles métier

## User

Un User possède exactement un Player.

---

## Player

Un Player peut exister sans User.

---

## Match

Un Match appartient toujours à son créateur.

---

## Teams

Toujours exactement deux Teams.

---

## MatchPlayer

Chaque MatchPlayer appartient :

- à un Match ;
- à une Team ;
- à un Player.

---

## Action

Une Action possède toujours :

- un Player ;
- un End ;
- un type ;
- une note.

La distance est optionnelle.

---

# Calcul des statistiques

Toutes les statistiques sont calculées à partir :

Player

↓

Actions

Aucune statistique n'est persistée.

---

# Hors périmètre

Le modèle ne contient volontairement pas :

- statistiques calculées ;
- classement ;
- historique agrégé ;
- premium ;
- réseau social.

Ces éléments seront construits à partir du modèle existant.