# ADR-001 - Identifiants des participants provisoires

## Contexte

Démarrer un match exige aujourd'hui que chaque joueur existe déjà en base.

`MatchService::create` résout tous les identifiants reçus et refuse un identifiant inconnu.

Quatre tables portent une clé étrangère non nulle vers `players` :

- `match_players`
- `match_tracked_players`
- `match_balls`
- `match_end_player_roles`

Aucune donnée de match ne peut donc exister sans ligne `Player`.

Le besoin métier est de composer les équipes avec des personnes non enregistrées, puis de les
rattacher ou de les créer à la fin du match.

Par ailleurs, `02-ux.md` spécifie déjà qu'aucune communication réseau n'a lieu pendant le match et
que le match est envoyé à la fin de la partie. Le code divergeait de cette spécification en créant
le match au démarrage. Conformément à `06-ai-rules.md`, la documentation est la référence.

---

## Décision

Un participant de match est soit un `Player` persisté, soit un participant provisoire.

Un participant provisoire est identifié par un **entier négatif** alloué dans la sauvegarde locale.

Un identifiant positif désigne toujours un `Player` en base.

Le concept n'existe que côté client. Il est matérialisé en `Player` non lié à la fin du match.

Le match n'est plus créé au démarrage. Le démarrage est entièrement local.

L'enregistrement se déroule en trois étapes réutilisant les endpoints existants :

1. `POST /api/players` pour chaque participant provisoire non résolu ;
2. `POST /api/matches` après remplacement des identifiants locaux ;
3. `POST /api/matches/{id}/complete`.

La progression est mémorisée dans la sauvegarde locale, ce qui rend l'enregistrement reprenable
sans créer de doublon.

Le backend reçoit une évolution additive : un champ optionnel `playedAt` sur
`CreateMatchRequest`, pour conserver la date réelle de début de partie.

Six Players techniques (A–F), identifiés par `placeholder_key`, absorbent les participants non
rattachés. Ils sont exclus de `trackedPlayers` pour ne pas fausser les statistiques.

---

## Alternatives

### Identifiant local sous forme de chaîne

Le moteur de jeu, les graphiques, le modèle live et la vue spectateur indexent tous les données par
`number` : mènes, balles, rôles, joueurs suivis, remplacements, noms d'affichage.

Passer à une clé `string` imposait un refactoring massif d'un code sensible, contraire à
`06-ai-rules.md`.

Rejetée.

### Clé étrangère `player_id` nullable avec un libellé en base

Le voter, le résumé, l'historique et les statistiques joignent tous `match_players.player`.

Il aurait fallu auditer et protéger chaque chemin de lecture, pour un gain nul côté métier.

Rejetée.

### Roster provisoire stocké en JSON sur `matches`

Imposait une migration et faisait coexister deux représentations du même roster, l'une provisoire,
l'autre définitive.

Rejetée pour cause de complexité permanente du modèle.

### Endpoint atomique unique

`02-ux.md` évoque l'envoi d'un unique DTO à la fin de la partie.

Un endpoint `POST /api/matches/finalize` aurait été transactionnellement plus propre, mais imposait
un DTO volumineux, une logique dupliquée avec `MatchService` et `MatchRecordingService`, et une
rupture de compatibilité avec les versions de l'application déjà déployées.

Écart assumé : l'intention de la documentation est respectée, aucune communication réseau n'a lieu
pendant le match et tout est envoyé à la fin, mais en trois appels au lieu d'un.

À reconsidérer si l'orchestration en trois étapes s'avère fragile en usage réel.

---

## Conséquences

### Avantages

Le démarrage d'un match est instantané et fonctionne hors ligne.

Aucun `Game` orphelin n'est plus créé en base par un match abandonné.

Le code se réaligne sur `02-ux.md`.

Aucune migration, aucun nouvel endpoint, aucune rupture de compatibilité.

Le test d'appartenance se réduit à un unique helper `isProvisional(id) => id < 0`.

Le match live n'est pas impacté : ses données sont un instantané JSON opaque côté backend.

Les noms d'affichage proviennent désormais de la sauvegarde locale, ce qui supprime l'affichage
transitoire `#id` au chargement de l'écran de jeu.

### Inconvénients

La sauvegarde locale devient la seule copie du match en cours. Les gestes destructifs doivent être
confirmés.

L'enregistrement final effectue trois appels réseau successifs, avec des états intermédiaires à
gérer explicitement.

La sauvegarde locale change de forme et impose une migration des parties en cours.

Le volume de `Player` non liés va augmenter, ce qui rend prioritaire l'implémentation de
`Player.visibility`.

Un identifiant négatif qui fuirait jusqu'à `POST /api/matches/{id}/complete` verrait ses balles
filtrées silencieusement par `MatchRecordingService`. Le remplacement des identifiants doit être
vérifié avant l'envoi.
