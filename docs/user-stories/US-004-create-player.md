# US-004 - Création d'un Player

## Objectif

Permettre la création d'un Player sans créer de compte utilisateur.

Le Player pourra être utilisé immédiatement dans les futurs matchs.

---

## Point d'entrée

Depuis le menu principal.

Bouton :

```
Ajouter un joueur
```

---

## Navigation

Au clic sur le bouton, ouvrir un nouvel écran.

Ne pas utiliser de popup.

Ne pas utiliser de dialogue.

L'écran occupe toute la page.

---

## Formulaire

Afficher uniquement les champs suivants.

- Prénom *
- Nom *
- Surnom

Le champ Club n'est pas encore affiché.

---

## Validation

Obligatoire :

- prénom
- nom

Optionnel :

- surnom

Afficher les erreurs de validation sous les champs.

---

## Enregistrement

Appel :

```
POST /api/players
```

Le backend crée uniquement un Player.

Aucun User ne doit être créé.

Le Player est créé avec :

```
user_id = null
```

---

## Payload

```json
{
    "firstName": "Dimitri",
    "lastName": "Chevalier",
    "nickname": "Dim"
}
```

---

## Réponse

HTTP 201

```json
{
    "id": 12,
    "firstName": "Dimitri",
    "lastName": "Chevalier",
    "nickname": "Dim"
}
```

---

## Succès

Après la création :

Retour automatique vers le menu principal.

Afficher un toast :

```
Joueur créé.
```

---

## Contraintes

Utiliser les composants PrimeVue.

Respecter l'UX mobile-first.

Tous les textes passent par vue-i18n.

Ne jamais utiliser `any`.

---

## Hors périmètre

Ne pas développer :

- modification d'un Player ;
- suppression ;
- recherche ;
- clubs ;
- statistiques ;
- matchs.

---

## Critères d'acceptation

Le formulaire s'affiche.

Les validations fonctionnent.

Le Player est créé en base.

Aucun User n'est créé.

Retour automatique au menu après création.

Le projet compile sans erreur.