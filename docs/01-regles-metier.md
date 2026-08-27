# Pétanque Analytics

# 01 - Règles métier

**Version :** 0.1

**Statut :** Validé

---

# Philosophie

Les statistiques sont produites à partir des Actions.

Aucune statistique n'est stockée en base.

Le Player est l'entité centrale du projet.

Le User est uniquement un moyen d'accéder à un Player.

---

# User

Le User représente un compte.

Il possède :

- une authentification ;
- un rôle (`SIMPLE_PLAYER` par défaut, `MASTER` pour l'administration) ;
- des préférences ;
- un abonnement (plus tard).

Chaque User est lié à un unique Player.

Le User ne possède aucune statistique.

---

# Player

Le Player représente une personne.

Il peut exister sans User.

Il peut être revendiqué plus tard par un User.

Un Player ne peut être revendiqué que par un seul User.

Toutes les statistiques appartiennent au Player.

---

# Club

Le Club est prévu dans le modèle dès la V1.

Il n'est pas développé fonctionnellement.

Il représente uniquement une information rattachée au Player.

---

# Match

Le Match représente une partie.

Il contient :

- les équipes ;
- les joueurs ;
- les mènes ;
- les actions ;
- le contexte.

Le Match appartient toujours au créateur.

---

# Équipes

Un Match possède toujours exactement deux équipes.

Le format du Match peut être :

- Tête-à-tête
- Doublette
- Triplette

---

# Participation

La participation d'un Player à un Match est représentée par MatchPlayer.

Elle contient notamment :

- le Player ;
- l'équipe ;
- le rôle initial ;
- l'analyse activée ou non.

---

# Analyse d'un joueur

Chaque Player possède un indicateur :

Analyser :

- Oui
- Non

Par défaut :

Tous les joueurs sont analysés.

Si l'analyse est désactivée :

- le Player participe au Match ;
- aucun Action n'est enregistrée pour lui ;
- le score reste enregistré normalement.

---

# Rôle

Le rôle initial est choisi au début du Match.

Valeurs possibles :

- Pointeur
- Milieu
- Tireur

Le rôle est uniquement une aide à la saisie.

Il ne détermine jamais les statistiques.

L'utilisateur peut ponctuellement changer le type d'action.

---

# Mène

Une mène contient :

- les Actions ;
- le résultat de la mène.

La distance n'appartient pas à la mène.

---

# Distance

Pendant la saisie d'une mène, une distance estimée peut être renseignée.

Cette information est :

- facultative ;
- modifiable à tout moment.

Lorsqu'une Action est créée, la valeur actuelle est copiée dans cette Action.

Si la distance change ensuite, seules les nouvelles Actions utilisent cette nouvelle valeur.

---

# Action

Une Action représente une boule jouée.

Elle possède toujours :

- un joueur ;
- un type ;
- une note ;
- un ordre.

Elle peut également posséder :

- une distance estimée.

Les Actions constituent la base de toutes les statistiques.

---

# Types d'action

Deux types existent :

- Point
- Tir

Le type est proposé automatiquement selon le rôle du joueur.

Il peut toujours être modifié.

---

# Modes de notation

Deux modes existent.

## Standard

Notes disponibles :

- +2
- +1
- 0
- -1
- -2

Il permet les statistiques les plus précises.

---

## Simple

Deux choix :

- Réussie
- Ratée

Conversion interne :

Réussie → +1

Ratée → -1

Le mode est choisi au début du Match.

Il reste identique jusqu'à la fin de la partie.

---

# Référentiel actuel

## +2

Action exceptionnelle.

Change clairement la mène.

---

## +1

Bonne action.

Apporte un avantage.

---

## 0

La boule joue.

Elle reste utile.

---

## -1

La boule ne joue pas.

Aucune utilité.

---

## -2

Erreur importante.

Dégrade fortement la mène.

Ce référentiel sera enrichi progressivement à partir de cas réels.

---

# Validation d'une mène

À la fin de chaque mène, l'utilisateur indique :

- quelle équipe marque ;
- combien de points.

Le score n'est jamais déduit automatiquement des Actions.

---

# Contexte du Match

À la fin de la partie, un écran facultatif permet de renseigner :

- type de partie ;
- compétition ;
- stade ;
- terrain ;
- commentaire.

Tous ces champs sont optionnels.

Ils servent uniquement aux analyses futures.

---

# Sauvegarde

Pendant toute la partie :

Le Match est géré uniquement dans le Front.

Le backend n'est jamais sollicité.

À la fin de la partie, un unique CreateMatchDTO est envoyé.

---

# Calcul des statistiques

Les statistiques :

- ne sont jamais stockées ;
- sont toujours calculées à partir des Actions.

Cela garantit leur cohérence, même après modification d'un Match.

## Pourcentage de réussite (point / tir)

Une statistique dérivée est calculée à partir des notes existantes, sans les modifier.

Conversion :

- +2 → réussite
- +1 → réussite
- 0 → non-réussite
- -1 → non-réussite
- -2 → non-réussite

Formule, séparément pour le point et pour le tir :

% réussite = nombre de boules notées +1 ou +2 / nombre total de boules notées

Les boules sans notation sont exclues.

S'il n'existe aucune boule notée pour un type de jeu, la statistique n'est pas affichée (pas de 0 % artificiel).

---

# Principe fondamental

Le produit privilégie toujours :

- la simplicité de saisie ;
- la rapidité ;
- la cohérence des données.

Une fonctionnalité ne doit jamais ralentir la saisie si elle n'apporte pas une réelle valeur analytique.