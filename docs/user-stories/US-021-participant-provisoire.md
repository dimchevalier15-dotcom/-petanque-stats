# US-021 - Participant provisoire

## Objectif

Permettre de démarrer un match immédiatement, sans avoir créé au préalable les Players en base.

Les joueurs inconnus sont saisis sous forme de simple libellé.

Ils sont rattachés à un Player existant, ou créés en base, uniquement à la fin du match.

---

## Problème actuel

Pour démarrer un match, chaque joueur doit déjà exister en base.

Ajouter un joueur inconnu impose de quitter l'écran de démarrage, de remplir un formulaire complet,
puis de revenir sur l'écran de démarrage.

Sur un terrain, avec des partenaires occasionnels, cette contrainte est bloquante.

---

## Règles métier

Un participant provisoire n'existe que dans la sauvegarde locale du match.

Il n'est jamais envoyé au backend en tant que joueur.

Un MatchPlayer référence toujours un Player réel, conformément à `04-modele-donnees.md`.

Aucune statistique n'existe avant la matérialisation des joueurs.

Un participant provisoire porte uniquement :

- un libellé saisi par l'utilisateur ;
- un identifiant local.

Un match ne peut pas être enregistré tant qu'il reste un participant provisoire non résolu.

Un Player créé à la fin d'un match est un Player non lié :

```
user_id = null
```

Il est identique à un Player créé via US-004.

---

## Point d'entrée

Écran de démarrage d'un match.

Dans le champ de recherche d'un joueur, si la saisie ne correspond à aucun Player :

proposer directement, dans la liste de suggestions, l'ajout du nom saisi comme participant du match.

Aucun formulaire.

Aucun changement d'écran.

Le même mécanisme est disponible dans le dialogue de changement de joueur pendant le match.

---

## Pendant le match

Aucune communication réseau, conformément à `02-ux.md`.

Le match est stocké localement.

Un participant provisoire est affiché exactement comme un joueur réel.

Le suivi des statistiques, les rôles et les remplacements fonctionnent à l'identique.

---

## Fin de match

Si le match ne contient aucun participant provisoire :

l'enregistrement est immédiat, aucun écran supplémentaire.

Si le match contient au moins un participant provisoire :

afficher un écran de résolution avant le résumé.

### Écran de résolution

Pour chaque participant provisoire, deux choix :

- rattacher un Player existant, par recherche ;
- créer un nouveau Player.

Champs de création :

- Prénom *
- Nom *
- Surnom
- Club

Le libellé saisi pendant le match est proposé comme valeur initiale.

Un même Player ne peut pas être affecté à deux participants du même match.

---

## Enregistrement

L'enregistrement se déroule en trois étapes.

La progression est mémorisée dans la sauvegarde locale.

En cas d'échec réseau, une nouvelle tentative reprend là où elle s'est arrêtée.

### Étape 1 - Joueurs

Pour chaque participant provisoire non encore résolu :

```
POST /api/players
```

L'identifiant obtenu est mémorisé localement.

Une nouvelle tentative réutilise les identifiants déjà obtenus.

Aucun Player en doublon ne peut être créé.

### Étape 2 - Match

Tous les identifiants locaux sont remplacés par les identifiants réels.

```
POST /api/matches
```

L'identifiant du match est mémorisé localement.

### Étape 3 - Contenu

```
POST /api/matches/{id}/complete
```

La sauvegarde locale est effacée uniquement après le succès de cette étape.

---

## Date du match

La date envoyée est la date de début du match, pas la date d'enregistrement.

Champ transmis lors de l'étape 2 :

```
playedAt
```

---

## Match live

Le match live n'est pas impacté.

Les données live sont un instantané d'affichage.

Les identifiants locaux et les libellés des participants provisoires y sont transmis tels quels.

Le spectateur voit les libellés saisis, jamais un identifiant technique.

---

## Contraintes

Utiliser les composants PrimeVue.

Respecter l'UX mobile-first.

Tous les textes passent par vue-i18n.

Ne jamais utiliser `any`.

Une partie en cours au moment de la mise à jour de l'application doit rester jouable et enregistrable.

---

## Hors périmètre

Ne pas développer :

- la visibilité des Players (`Player.visibility`) ;
- le filtrage des Players dans la recherche globale ;
- la suppression d'un participant provisoire après enregistrement ;
- plusieurs matchs en cours simultanément.

---

## Dette identifiée

Un Player créé à la fin d'un match devient visible dans la recherche globale de tous les
utilisateurs.

`Player.visibility` est prévu dans `04-modele-donnees.md` mais n'est pas implémenté.

Cette évolution augmente le volume de Players non liés et rend ce sujet prioritaire.

---

## Critères d'acceptation

Un match démarre sans aucun appel réseau.

Un joueur inconnu est ajouté sans quitter l'écran de démarrage.

Un participant provisoire peut être suivi, changer de rôle et être remplacé.

Un match sans participant provisoire s'enregistre sans écran supplémentaire.

Un match avec participants provisoires impose leur résolution avant l'enregistrement.

Un échec réseau pendant l'enregistrement ne perd aucune donnée et ne crée aucun doublon.

La date enregistrée est la date de début du match.

Le match live continue de fonctionner et affiche les libellés saisis.

Une partie démarrée avant la mise à jour reste enregistrable.

Le projet compile sans erreur.
