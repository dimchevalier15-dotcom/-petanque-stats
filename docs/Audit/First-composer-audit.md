# Audit complet — Pétanque Analytics

Analyse en lecture seule du dépôt (`api/`, `mobile/`, `docs/`). Aucun fichier n’a été modifié.

---

## 1. Compréhension du produit

### Ce que fait l’application

Application web mobile-first (Vue 3 + Capacitor) couplée à une API Symfony, destinée à **enregistrer une partie de pétanque boule par boule** et à en tirer des **statistiques de performance** (notes -2 à +2, ou mode simple réussite/ratée).

### Utilisateur cible

Joueur compétiteur, entraîneur ou joueur régulier qui veut suivre sa forme et celle de ses coéquipiers/adversaires, sans ralentir le jeu.

### Parcours principal

1. Inscription / connexion (JWT)
2. Création d’un match (format, joueurs, mode de notation, joueurs analysés ou non)
3. Saisie des mènes en direct (notes par boule, validation manuelle du score de mène)
4. Fin de partie → envoi unique au backend
5. Résumé immédiat + contextualisation optionnelle
6. Consultation ultérieure : historique, stats globales, guidelines

### Proposition de valeur actuelle

- Saisie structurée par boule avec distinction point/tir
- Statistiques calculées à la volée (pas stockées)
- Résumé visuel post-match (graphiques, moyennes)
- Historique et stats agrégées pour le joueur lié au compte
- Documentation intégrée de l’échelle de notation

### Cœur du produit

Le **Player** et ses **Actions** (boules notées). Le match est le véhicule ; les stats en sont le produit. L’écran de jeu (`MatchPlayView`) est le point de friction principal : s’il est lent ou fragile, tout le produit échoue.

---

## 2. Fonctionnalités existantes

| Fonctionnalité | Statut | Commentaire |
|---|---|---|
| Auth (register/login/me/logout) | **Fonctionnelle** | JWT manuel, session localStorage |
| Lien User ↔ Player | **Fonctionnelle** | À l’inscription ou via paramètres |
| Création de joueurs | **Fonctionnelle** | Recherche + création rapide |
| Création de match | **Fonctionnelle** | Tête-à-tête, doublette, triplette |
| Saisie de mène (notes, point/tir) | **Fonctionnelle** | Standard et simple |
| Mène annulée (but annulé) | **Partiellement fonctionnelle** | UI + backend OK, mais stats faussées (voir §4) |
| Fin de mène anticipée | **Fonctionnelle** | Hint + validation sans toutes les boules |
| Fin de match avant 13 | **Fonctionnelle** | Bouton « Terminer » toujours disponible |
| Suggestion auto du score de mène | **Fonctionnelle** | `EndScoreSuggestion` (récent), pré-remplit le dialogue |
| Envoi match complet | **Fonctionnelle** | `POST /matches/{id}/complete` |
| Résumé post-match | **Fonctionnelle** | Scores, moyennes, graphiques |
| Contexte post-match | **Fonctionnelle** | Nature, compétition, terrain, commentaire |
| Historique paginé | **Fonctionnelle** | 20/page, clic → résumé |
| Stats joueur | **Fonctionnelle** | KPIs, évolution, point/tir, par nature |
| Guidelines / doc notation | **Fonctionnelle** | Écran complet i18n |
| i18n (fr/en/sk) | **Partiellement fonctionnelle** | EN incomplet, locale non restaurée au boot |
| Sauvegarde locale du match en cours | **Absente** | Doc prévoit MatchDraft — non implémenté |
| Reprise de partie interrompue | **Absente** | |
| Correction d’une mène passée | **Absente** | Navigation oui, édition non |
| Distance estimée par action | **Absente** | Prévue dans `docs/01-regles-metier.md` |
| Distance dans le modèle | **Absente** | Pas de champ sur `GameBall` |
| Créateur du match | **Absente** | Doc dit « match appartient au créateur » — pas de FK |
| Club / Venue | **Absente** (modèle partiel) | `club` sur Player en base, pas d’API |
| PWA / offline | **Absente** | |
| Capacitor natif | **Absente** | Config seulement |
| Tests backend | **Absents** | PHPUnit configuré, 0 test |
| Tests frontend | **Quasi absents** | 1 fichier (8 tests sur `EndScoreSuggestion`) |
| CI | **Absente** | Pas de `.github/` |
| Déploiement production | **Absent** | Docker dev uniquement |
| Sécurisation des endpoints match | **Fragile** | Pas d’auth ni ownership |
| Idempotence complete | **Fragile** | Re-post duplique ends/balls |
| Gestion erreurs finish match | **Fragile** | Pas de feedback utilisateur |
| `AddPlayerView` | **Fragile** | Erreur de syntaxe template ligne 2 (`""`) |

---

## 3. Ce qui manque pour une vraie V1

### CRITIQUE

| Élément | Pourquoi | Impact utilisateur | Difficulté | Priorité |
|---|---|---|---|---|
| **Persistance locale du match en cours** | Doc + vision V1 ; état uniquement en mémoire + query string | Perte totale sur refresh, appel, navigation | Moyenne | P0 |
| **Sécurisation API match** (auth + ownership) | Tout le monde peut lire/modifier/compléter n’importe quel match par ID | Fuite de données, corruption | Moyenne | P0 |
| **Config API URL production** | `http.ts` hardcodé `localhost:8080` | App inutilisable hors dev Docker | Faible | P0 |
| **Tests métier critiques backend** | 0 test sur stats, complete, auth | Régressions silencieuses sur le cœur produit | Moyenne | P0 |
| **Feedback erreur fin de match** | `confirmFinish` sans catch/UX | Partie « perdue » côté utilisateur sans savoir pourquoi | Faible | P0 |
| **Exclusion des boules des mènes annulées des stats** | Agrégats SQL ne filtrent pas `canceled` | Stats faussées après but annulé | Faible | P0 |

### IMPORTANT

| Élément | Pourquoi | Impact | Difficulté | Priorité |
|---|---|---|---|---|
| **Correction des mènes passées** | Documenté dans `docs/02-ux.md` | Erreur de saisie = données définitives | Moyenne | P1 |
| **Idempotence de `complete`** | Re-soumission duplique | Stats et scores doublés | Moyenne | P1 |
| **Lier joueur obligatoire ou onboarding clair** | Sans Player lié : historique/stats vides | Confusion « l’app ne marche pas » | Faible | P1 |
| **Restauration locale i18n** | `locale` sauvée mais jamais lue | Langue reset à chaque visite | Très faible | P1 |
| **Traductions EN manquantes** | 8 clés (cancel end) | UI cassée en anglais | Très faible | P1 |
| **JWT intégré au firewall Symfony** | Auth manuelle, incohérente, 500 possibles | Comportement imprévisible | Moyenne | P1 |
| **Matchs incomplets dans l’historique** | Création sans complete → 0-0 dans l’historique | Bruit, défaites fantômes | Faible | P1 |
| **CORS / HTTPS prod** | CORS limité à localhost:5173 | Bloque tout déploiement web | Faible | P1 |
| **Fix syntaxe `AddPlayerView`** | Template invalide | Création joueur potentiellement cassée | Très faible | P1 |

### NICE TO HAVE

| Élément | Pourquoi | Impact | Difficulté |
|---|---|---|---|
| Distance estimée | Doc métier | Stats futures par distance | Moyenne |
| Refresh token JWT | Expiration 1h | Reconnexion en cours de partie longue | Moyenne |
| PWA basique | Offline partiel | Confort terrain | Moyenne |
| Capacitor Android build | Distribution store | Adoption mobile native | Moyenne |
| Filtres historique | US-013 dit non pour V1 | UX power users | Faible |
| OpenAPI | Doc API auto | Dev intégration | Moyenne |

### PAS NÉCESSAIRE (maintenant)

- Clubs, réseau social, classements, IA
- CQRS, Event Sourcing, Redis, API Platform
- Refonte Flutter (AGENTS.md obsolète — le code est Vue)
- Migration PostgreSQL (MySQL fonctionne)
- Statistiques pré-calculées en base
- Refonte complète de l’architecture front/back

---

## 4. Audit métier pétanque

### Ce qui est bien couvert

| Situation | Comportement |
|---|---|
| Formats tête-à-tête / doublette / triplette | Tailles d’équipe validées front + back |
| 3 boules (2 en triplette) | Cohérent |
| Mode standard (-2…+2) et simple (+1/-1) | OK |
| Joueur non analysé | Pas de saisie de boules, mais présent dans l’équipe |
| Rôle → type d’action par défaut | Pointeur/milieu → point, tireur → tir |
| Changement point/tir à la saisie | OK |
| Score de mène manuel | OK (suggestion auto mais modifiable) |
| Mène annulée | 0 point, mène enregistrée `canceled=true` |
| Fin de mène avant toutes les boules | `earlyHint` + validation possible |
| Fin de match avant 13 | Bouton terminer sans garde-fou score |
| Contexte fin de partie | Optionnel, skippable |

### Incohérences avec `docs/`

| Règle doc | Réalité code | Gravité |
|---|---|---|
| Match stocké localement pendant la partie | **Aucune persistance** | Critique |
| Reprendre un MatchDraft | Absent | Critique |
| Modifier/supprimer une action, revenir sur mène précédente | Navigation oui, **édition bloquée** une fois mène validée (`isEndScored`) | Important |
| Distance copiée dans chaque Action | **Champ inexistant** partout | Important (V1 doc) |
| Score jamais déduit des Actions | `EndScoreSuggestion` pré-remplit winner/points depuis les notes | Acceptable si modifiable, mais risque d’usage « automatique » |
| Match appartient au créateur | Pas de `creator_id` sur `Game` | Important (sécurité) |
| Historique/stats hors V1 initiale | Implémentés (évolution produit OK) | Info |
| Égalité au score | `scoreA >= scoreB` → A gagne | Cas rare mais biais stats V/D |

### Cas limites terrain

| Cas | Comportement actuel | Problème |
|---|---|---|
| **But sorti / mène annulée** | Mène canceled + boules saisies conservées en base | **Boules comptées dans les stats** malgré mène annulée |
| **Boules restantes** | Pas de compteur ; saisie séquentielle par joueur | OK en usage normal ; pas de notion « il reste X boules » |
| **Adversaires non trackés** | Affichés sans boutons de saisie | OK mais encombre l’écran |
| **Match créé mais jamais terminé** | Reste en base, apparaît en historique 0-0 | Pollution |
| **Double complete** | Duplique ends/balls | Corruption données |
| **Suggestion score = 0 d’écart** | Propose 1 point minimum | Cohérent pétanque |
| **Égalité notes entre équipes** | `winner: null`, points = 1 | Utilisateur doit choisir manuellement — OK |

---

## 5. Audit des statistiques

### Enregistrement

- Chaque boule → `match_balls` (note, shot_type, player, end)
- Chaque mène → `match_ends` (winner, points, canceled)
- Rien n’est pré-agrégé — conforme à la doc

### Données disponibles

- Par match : moyenne, distribution -2…+2, split point/tir par joueur tracké
- Cross-match : W/L, win rate, évolution, breakdown par nature de partie
- **Non disponible** : distance, par rôle, par adversaire, par format, tendance par type de terrain

### Calcul

`GameBallRepository` fait des agrégats SQL (COUNT, SUM, GROUP BY). Propre et extensible.

### Incohérences / pièges

| Problème | Effet |
|---|---|
| Boules des mènes `canceled` incluses | Moyennes faussées après but annulé |
| Égalité → victoire équipe A | Win rate légèrement biaisé |
| `statisticsMode` stocké mais ignoré | Mode simple traité comme notes natives (-1/+1) — OK en soi, mais pas de distinction dans les stats |
| Matchs sans boules trackées | États `no_tracked_data` gérés — bien |
| Historique inclut matchs sans ends | Victoires/défaites sur 0-0 |
| Moyenne = somme des notes / nombre de boules | Pas de pondération par mène ou importance — assumé par le produit |
| `EndScoreSuggestion` | Peut inciter à valider un score « calculé » vs réel terrain |

### Extensibilité du modèle

**Bonne base** : ajouter distance, filtres, nouvelles dimensions = migration + enrichissement des agrégats. Pas besoin de recalculer des tables de stats.

### Stats potentiellement trompeuses sans que l’UI le signale

- Moyenne globale mélange entraînement et compétition (sauf section « par nature » si renseigné)
- Win rate inclut matchs abandonnés/non terminés
- Joueur jamais tracké dans ses propres matchs → stats vides sans explication claire avant le 1er match tracké

---

## 6. Audit UX/UI

### Onboarding

**Faible.** Pas d’explication du produit à l’arrivée. L’accueil montre le titre, l’email connecté et des actions secondaires (ajouter joueur, guidelines, settings). **« Nouvelle partie » est dans la bottom nav**, pas sur l’accueil — un nouvel utilisateur peut ne pas comprendre immédiatement quoi faire.

### Création de match

**Correcte et assez rapide** : un écran, recherche joueurs, quick-add, rôles, mode notation. Friction : recherche min. 3 caractères, pas de joueurs récents.

### Déroulé pendant une vraie partie

**Utilisable mais fragile** :
- Points forts : sticky scoreboard, gros boutons boules, bottom sheet score, graphique forme en cours
- Faiblesses : pas de sauvegarde, query string fragile, pas de correction mène passée, finish sans feedback erreur, adversaires non trackés prennent de la place

### Fin de match

**Bonne** : dialogue de confirmation, résumé riche (hero, comparaison, cartes joueurs), contexte optionnel.

### Historique

**Utile mais minimal** : date, type, score, V/D. Manque noms d’équipes/adversaires (prévu dans US-013 mais pas affiché). Pas de gestion d’erreur chargement.

### Statistiques

**Attrayant** si données présentes (graphiques, KPIs). États vides bien gérés. **Meilleure UX d’erreur** du projet (retry).

### Guidelines

**Très bon** : pédagogique, bien structuré, répond au besoin « comment noter ? ».

### Navigation

**Claire** : bottom nav (Home, Historique, Stats, + Nouvelle partie). `PageHeader` avec retour sur écrans focus. Play layout sans nav — correct.

### Mobile

**Bon travail CSS** : `100dvh`, safe areas, touch targets, max-width 560px, grilles adaptatives. Quelques écrans denses en triplette (6 joueurs × 3 boules).

---

## 7. Audit technique

### Points solides (à ne pas sur-modifier)

- Architecture back simple et lisible : Controller → Service → Repository → Entity
- DTO Symfony avec validation
- Séparation front `models/` vs `dto/` globalement respectée
- Composables métier (`useMatchPlay`, `useNewMatchSetup`)
- Agrégats stats en SQL, pas en PHP naïf
- Thème CSS cohérent, mobile-first
- i18n structurellement en place

### Problèmes réels pour V1

| Zone | Problème |
|---|---|
| **État match** | Tout dans query string + mémoire — anti-pattern pour données critiques |
| **Auth** | Double système (manuel + firewall vide) |
| **Sécurité données** | Endpoints match/player publics |
| **Tests** | Quasi inexistants |
| **Doc vs code** | AGENTS.md (Flutter/PostgreSQL) ≠ réalité (Vue/MySQL) |
| **Erreurs API** | Formats inconsistants (`errors` / `error` / `message`) |
| **Dead code** | Placeholders, `AuthService.ts`, `RegisterController` vide |
| **Typage front** | `as unknown as` sur summary/auth |
| **Pas de CI** | Régressions non détectées |
| **Logique métier front** | `EndScoreSuggestion` en model TS — acceptable pour UX, mais doc dit « pas de logique métier mobile » |

### Dette acceptable pour V1

- Pas de refresh token
- Repositories avec requêtes multiples (optimisable plus tard)
- Pas d’OpenAPI
- Pinia limité au auth store
- Pas de couche Domain PHP séparée (DDD léger suffit)

---

## 8. Audit sécurité

### Exploitable / réel

| Risque | Détail |
|---|---|
| **IDOR match** | `GET/PUT /matches/{id}/context`, `GET /summary`, `POST /complete` sans auth |
| **Spam création match** | `POST /matches` sans auth ni rate limit |
| **Joueur global** | `GET /players`, `GET /players/{id}` publics — fuite noms |
| **Complete non idempotent** | Corruption données par rejeu de requête |
| **JWT 1h, pas de refresh** | Expiration en session longue |
| **Register catch générique** | Peut exposer `$e->getMessage()` en 400 |
| **Token invalide sur history/stats** | Risque 500 (pas catché comme account) |
| **CORS localhost only** | OK dev, bloque prod si mal configuré |
| **Pas de .env versionné** | Bien — mais pas de `.env.example` visible |

### Théorique / amélioration

- Rate limiting
- RBAC Symfony
- Audit log
- Chiffrement at-rest
- Validation OWASP exhaustive

### Mots de passe

Hashés via Symfony password hasher — **OK**.

---

## 9. Audit performance

### Ce qui casse en premier

| Échelle | Point de rupture probable |
|---|---|
| **100 → 1 000 users** | Peu de risque si usage modéré |
| **1 000 → 10 000 users** | Table `match_balls` qui grossit ; `GET /players/me/stats` charge **tous** les matchs complétés + agrégats multiples sans pagination |
| **Historique** | Pagination OK (20/page) |
| **Recherche joueurs** | `LIKE` sans index fulltext — lent à grande échelle |
| **Complete match** | Transaction avec N inserts — OK par requête |
| **Stats par match** | 2-3 requêtes agrégées — OK |

### Verdict

Pas d’optimisation prématurée nécessaire pour une V1 à ~100 utilisateurs actifs. **Le premier goulot réel** : endpoint stats joueur sans limite temporelle/pagination quand le volume de boules dépasse quelques dizaines de milliers.

---

## 10. Audit production

### Nécessaire avant premier test réel (beta fermée)

- URL API configurable (`VITE_API_URL` ou proxy nginx)
- HTTPS (même self-signed ou tunnel)
- CORS pour le domaine de test
- Variables JWT (`JWT_SECRET_KEY`, etc.) documentées
- Migrations auto au deploy
- Fix persistance match (sinon tests terrain impossibles)
- Fix sécurité minimale (auth sur write endpoints)
- Feedback erreur sur complete
- `.env.example` api + mobile

### Nécessaire avant mise en production publique

- HTTPS obligatoire + certificats
- `APP_ENV=prod`, `APP_DEBUG=0`
- Secrets hors repo (vault, CI secrets)
- Sauvegardes MySQL automatisées
- Monitoring (Sentry ou équivalent) + alertes
- Logs structurés (Monolog prod → stderr JSON : déjà prévu)
- Build frontend statique servi par nginx/CDN
- Capacitor build signé OU PWA installable
- Rate limiting / WAF basique
- Politique de rétention données
- Tests automatisés en CI
- Documentation deploy recette/prod (actuellement 9 lignes dans `docs/deployment.md`)

---

## 11. Tests

### Testé

- `EndScoreSuggestion` : 8 tests unitaires (sommes, suggestion, clamp) — **bon rapport effort/confiance**

### Non testé (critique)

| Zone | Scénarios manquants |
|---|---|
| `MatchRecordingService` | Mène annulée, triplette 2 boules, notes invalides ignorées |
| `MatchSummaryService` | Exclusion canceled, égalité |
| `PlayerStatsService` | W/L, no_tracked_data, by nature |
| `MatchService` | Validation équipes, tracked players |
| Auth | Login, register, token expiré |
| `useMatchPlay` | toSubmission, cancel end, finish early |
| E2E | Parcours complet create → play → complete → summary |

### Bugs faciles à introduire

- Filtrage mènes annulées dans stats
- Idempotence complete
- Query string match corrompue
- Tie-break victoire
- Mapping rôle milieu → shot type
- Clés i18n EN

### Meilleur ROI tests (peu de temps, haute confiance)

1. Tests PHPUnit `MatchRecordingService` + `GameBallRepository` (canceled filter)
2. Tests PHPUnit `MatchSummaryService` / `PlayerStatsService` (3-4 cas chacun)
3. Test `useMatchPlay.toSubmission()` (mène annulée, early finish)
4. Un test fonctionnel API : register → create → complete → summary

---

## 12. Ne pas faire maintenant

- Migrer vers Flutter (AGENTS.md obsolète)
- API Platform / GraphQL
- Architecture hexagonale / CQRS
- Clubs, social, classements, premium
- Stats pré-calculées en base
- Refonte complète auth (OAuth, magic links)
- IA d’analyse de performance
- Synchronisation temps réel multi-appareils
- Refonte UI complète
- Distance estimée **avant** persistance match et correction mènes
- Optimisation SQL prématurée
- Microservices
- Event sourcing des actions

---

## 13. Verdict final

### V1 aujourd’hui

**Maturité : 5,5 / 10**

Le squelette produit est là et le parcours happy path fonctionne en local. Mais l’application n’est **pas prête pour de vrais utilisateurs en conditions terrain** : perte de données, sécurité ouverte, stats parfois fausses, zéro filet de tests backend.

### Pour sortir (5–10 actions max)

1. **Persistance locale du match** (localStorage/IndexedDB + reprise sur l’accueil)
2. **Auth + ownership** sur create/complete/context (au minimum : token obligatoire, match lié au user)
3. **URL API configurable** pour build production
4. **Exclure les mènes annulées des agrégats de boules**
5. **Idempotence de `complete`** (supprimer ends/balls existants avant réinsert)
6. **Feedback erreur** sur fin de match + retry
7. **Tests PHPUnit** sur recording + summary + stats (10–15 tests ciblés)
8. **Fix `AddPlayerView`** + clés EN manquantes + restauration locale
9. **Filtrer l’historique** aux matchs avec au moins 1 mène complétée
10. **`.env.example` + doc deploy minimal** pour recette

### Après les premiers utilisateurs — attendre leurs retours sur

- Correction de mènes : indispensable ou acceptable ?
- Suggestion auto du score : aidante ou dangereuse ?
- Distance : vraiment saisie en match ?
- Mode simple vs standard : répartition d’usage
- Track partiel (soi seul vs toute l’équipe)
- Besoin offline/PWA vs APK
- Lisibilité écran play en plein soleil
- Pertinence des stats affichées (trop / pas assez)

### Risques majeurs

1. **Perte d’une partie entière** (pas de draft) — tue la confiance immédiatement
2. **Données accessibles/modifiables par ID** — incident sécurité ou corruption
3. **Stats faussées** (mènes annulées, doubles complete, matchs fantômes) — produit « analytics » non fiable
4. **Zéro tests backend** — chaque fix peut casser le cœur métier
5. **Déploiement non préparé** — impossible de sortir du Docker local

### Ce qui est déjà suffisamment bon — ne pas y toucher inutilement

- Structure monorepo et Docker dev
- Modèle de données boule/mène/joueur (solide)
- Écran de jeu et sa création (UX globalement bonne)
- Résumé post-match et graphiques
- Guidelines
- Bottom nav et thème mobile
- Calcul stats à la volée (bon choix architectural)
- Séparation services/composables
- Pagination historique

---

**Note documentaire** : `AGENTS.md` décrit Flutter/PostgreSQL/API Platform ; le code réel est **Vue 3 / MySQL / REST custom**. La doc `docs/05-architecture.md` est plus fidèle. En cas de divergence, traiter `docs/` comme source métier et mettre à jour `AGENTS.md` séparément — hors scope de cet audit.