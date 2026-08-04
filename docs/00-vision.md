# Pétanque Analytics

## 00 - Vision

**Version :** 0.1
**Statut :** Validé

---

# Objectif

Développer une application mobile permettant d'enregistrer rapidement une partie de pétanque afin de produire des statistiques fiables sur les performances des joueurs.

L'application est conçue pour les joueurs, les entraîneurs et, à terme, les clubs.

Le produit n'est **pas** un simple marqueur de score.

---

# Philosophie

Le projet est guidé par les principes suivants :

* Simplicité avant tout.
* Le moins d'écrans possible.
* Le moins de clics possible.
* Toute donnée non indispensable est facultative.
* Toute fonctionnalité doit répondre à un besoin réel.

Chaque nouvelle fonctionnalité doit répondre à au moins un des objectifs suivants :

* accélérer la saisie ;
* améliorer les statistiques ;
* améliorer l'expérience utilisateur.

Sinon, elle est reportée.

---

# Cœur du produit

Le cœur du projet est le **Player**.

Les matchs servent à produire des données.

Les statistiques sont calculées à partir des Actions réalisées par les Players.

Le compte utilisateur n'est qu'un moyen d'accéder à un Player.

---

# Priorités V1

* Authentification.
* Gestion des Players.
* Création d'un match.
* Saisie rapide des mènes.
* Sauvegarde locale pendant le match.
* Envoi du match terminé.
* Calcul des premières statistiques.

---

# Hors périmètre V1

* Réseau social.
* Chat.
* Likes.
* Commentaires.
* Notifications.
* Classements.
* Clubs (fonctionnellement).
* Premium.
* Synchronisation temps réel.
* Match en direct.
* Intelligence artificielle.

Ces fonctionnalités pourront être étudiées après validation du produit.

---

# Public cible

En priorité :

* joueurs compétiteurs ;
* entraîneurs ;
* joueurs réguliers.

L'application doit néanmoins rester accessible aux joueurs occasionnels grâce à un mode de notation simplifié.

---

# Internationalisation

Le projet est nativement multilingue.

Principes :

* aucune chaîne de caractères en dur dans l'interface ;
* toutes les chaînes passent par le système de traduction ;
* le code est écrit en anglais ;
* la documentation est rédigée en français.

Langues prévues :

* Français
* Anglais
* Slovaque

L'ajout d'une nouvelle langue ne doit nécessiter aucune modification du code métier.

---

# Principes techniques

Le métier pilote la technique.

Le code doit rester simple, lisible et maintenable.

Aucune complexité ne doit être ajoutée en anticipation d'une hypothétique V2.

---

# Définition du succès

Une V1 est considérée comme réussie si un joueur ou un entraîneur peut :

* créer une partie en quelques secondes ;
* enregistrer une partie complète sans ralentir le jeu ;
* consulter des statistiques fiables à la fin du match.
