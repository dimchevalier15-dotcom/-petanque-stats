# US-013 - Historique des matchs

## Contexte

L'utilisateur peut désormais :

- créer un match ;
- jouer un match ;
- enregistrer le résultat ;
- consulter le résumé immédiatement après la fin du match.

Il manque désormais un moyen de retrouver ses anciens matchs.

Respecter strictement `docs/06-ai-rules.md`.

En particulier :

- aucune logique métier dans les composants Vue ;
- respecter l'architecture Backend (Controller → Service → Repository) ;
- utiliser des DTO ;
- utiliser les Models côté frontend ;
- ne pas déclarer de types locaux ;
- tous les textes passent par i18n.

---

# Objectif

Ajouter un écran "Historique des matchs".

Pour cette première version :

- aucune recherche ;
- aucun filtre ;
- aucun tri configurable.

Simplement la liste paginée des matchs du joueur connecté.

---

# Menu principal

Ajouter un nouveau bouton.

```
Historique des matchs
```

Utiliser une icône PrimeVue adaptée.

Le style doit rester cohérent avec les autres boutons du menu.

---

# Source des données

L'historique affiche uniquement les matchs auxquels participe le Player associé au User actuellement connecté.

Ne jamais utiliser directement l'utilisateur.

Le lien est :

```
User
    ↓
Player
    ↓
MatchPlayer
    ↓
Match
```

---

# Backend

Créer un endpoint dédié.

Exemple :

```
GET /api/matches/history
```

Le endpoint retourne uniquement les matchs du joueur connecté.

---

# Pagination

La pagination est obligatoire.

Première version :

20 matchs par page.

Le backend doit gérer :

- page ;
- taille de page.

Le frontend doit permettre de charger les pages suivantes.

Utiliser les composants PrimeVue adaptés.

---

# Informations affichées

Chaque ligne représente un match.

Afficher uniquement :

- date du match ;
- type (Tête-à-tête / Doublette / Triplette) ;
- score final ;
- victoire / défaite.

Ne pas afficher les statistiques.

Ne pas afficher les détails des joueurs.

L'objectif est une liste simple.

Exemple :

```
05/08/2026

Doublette

Dimitri / Guy
vs
Lucas / Antoine

13 - 8

🟢 Victoire
```

---

# UX

Application mobile.

Chaque match est affiché sous forme de carte.

Éviter les tableaux HTML.

Privilégier :

- Card ;
- Panel ;
- ou une simple liste avec séparateurs.

Chaque carte doit être entièrement cliquable.

---

# Navigation

Au clic sur un match.

Rediriger vers :

```
/matches/:id/summary
```

Le composant Summary existant est réutilisé.

Aucune nouvelle page de détail n'est créée.

---

# Repository

Créer une méthode explicite.

Exemple :

```
findHistoryForPlayer(
    int $playerId,
    int $page,
    int $pageSize
)
```

Les QueryBuilder restent dans le Repository.

---

# DTO

Créer les DTO nécessaires.

Exemple :

```
MatchHistoryItemResponse

MatchHistoryResponse
```

Le Controller ne retourne jamais directement les Entities.

---

# Frontend

Créer :

```
MatchHistoryView
```

Créer les Models et DTO nécessaires.

Ne jamais déclarer de types directement dans le composant.

---

# Contraintes

Ne modifier :

- ni le déroulement d'un match ;
- ni le résumé existant ;
- ni les statistiques.

Cette User Story consiste uniquement à permettre de retrouver les anciens matchs.

---

# Livrable

Fournir uniquement :

- les fichiers créés ;
- les fichiers modifiés ;
- les éventuels points d'attention.