# Google Play Data Safety — Pétanque Stats

Analyse basée sur le code réel (`mobile/` + `api/src`), pas sur des possibilités théoriques.

Application Android : `com.petanquestats.app`  
Backend : `https://api.petanque-analytics.com` (HTTPS)  
Pas de SDK analytics, crash reporting, publicité ou Firebase dans l’application.

---

## Transport

Toutes les requêtes API du build production passent par Axios (`mobile/src/services/http.ts`) vers `VITE_API_URL` = `https://api.petanque-analytics.com/api`.

- Chiffrement en transit : HTTPS (TLS via Caddy / Let’s Encrypt).
- HTTP clair : interdit (`usesCleartextTraffic="false"`).
- Plugin Capacitor : `CapacitorHttp` activé (requêtes natives HTTPS, sans changer le métier).

---

## Données collectées et transmises au serveur

Collectées uniquement si l’utilisateur utilise la fonctionnalité correspondante. Compte requis pour l’app (inscription / connexion).

### Compte

| Donnée | Collectée | Stockée serveur | Transmise | Obligatoire | Finalité |
| --- | --- | --- | --- | --- | --- |
| Adresse email | Oui | Oui (`users.email`) | Oui (inscription, connexion, `/me`) | Oui | Authentification, identifiant de compte |
| Mot de passe | Oui (saisi) | Hash uniquement (`users.password`) | Oui à l’inscription / connexion, jamais renvoyé | Oui | Authentification |
| JWT | Émis par l’API | Non (côté client) | Authorization Bearer | Oui une fois connecté | Session (~14 jours, `token_ttl: 1209600`) |

Pas de numéro de téléphone, pas d’OAuth tiers, pas de photo de profil.

### Profil joueur (Player)

| Donnée | Collectée | Stockée serveur | Obligatoire | Finalité |
| --- | --- | --- | --- | --- |
| Prénom | Oui | Oui | Oui pour créer / lier un joueur | Identifier le joueur dans matchs et stats |
| Nom | Oui | Oui | Oui | Idem |
| Surnom | Oui | Oui (peut être vide selon flux) | Non (saisie libre) | Affichage |
| Champ `club` en base | Colonne Doctrine nullable | Oui si renseignée | Non exposé dans l’UI actuelle | Non utilisé par le frontend V1 |

L’utilisateur peut lier un Player existant ou en créer un à l’inscription.

### Matchs

Transmis **uniquement** quand le match est terminé et sauvegardé (brouillon local uniquement avant envoi).

Données typiques persistées : format, score cible, mode de stats, nature, noms d’équipes optionnels, nom / phase de compétition optionnels, type de terrain optionnel, commentaire optionnel, participants (lien vers des Players), mènes, rôles, boules (note, type point/tir, distance optionnelle).

Les statistiques de match **ne sont pas stockées** : elles sont calculées à la lecture à partir des actions.

### Entraînement (point / tir)

Session liée à un Player : type, distance, nombre de boules, tentatives, scores, dates.

### Tir de précision

Session liée à un Player : 20 tirs (ateliers × distances), scores, titre / description / nature de contexte optionnels, dates.

### Identifiants techniques

| Donnée | Collectée par l’app ? | Détail |
| --- | --- | --- |
| Advertising ID | Non | Aucun SDK pub |
| Analytics / crash | Non | Pas de Sentry, Firebase, GA |
| IP | Pas par l’app | Le serveur HTTPS voit l’IP comme tout site (logs reverse proxy possibles, hors code métier) |
| Device ID / contacts / localisation GPS | Non | Aucun plugin Capacitor Camera, Geolocation, Contacts |

### Stockage sur l’appareil

| Donnée | Où | Finalité |
| --- | --- | --- |
| JWT | `localStorage` (`auth_token`) | Session |
| Langue | `localStorage` (`locale`) | i18n |
| Brouillon de match | `localStorage` | Reprise d’une partie en cours |
| Brouillon tir de précision | `localStorage` | Reprise d’une séance en cours |

`android:allowBackup="false"` : ces données ne sont pas sauvegardées via la sauvegarde Android.

---

## Partage avec des tiers

Aucun SDK tiers de tracking, pub ou crash dans `mobile/package.json` (hors UI PrimeVue / Chart.js, exécutés localement).

Hébergement : VPS (API, MySQL, frontend). Pas de sous-traitant analytics identifié dans le code.

Google Play / Google Play App Signing : Google reçoit l’AAB et peut collecter des données de distribution Play (hors app). À déclarer selon le formulaire Play si demandé pour « services Google », pas comme collecte in-app.

---

## Suppression

- Déconnexion : supprime le JWT local, pas le compte serveur.
- Suppression de compte in-app : **Paramètres → Supprimer mon compte** (`DELETE /api/account`).
- URL web (Play Console) : `https://petanque-analytics.com/delete-account` (page publique ; la suppression exige une connexion).
- In-app : **Paramètres → Supprimer mon compte**.
- Effet : suppression de l’utilisateur (email, mot de passe, tokens). Le joueur lié est détaché (`user_id` à NULL) pour conserver l’historique des matchs des autres.

---

## Réponses types formulaire Data Safety (à valider)

- L’app collecte-t-elle des données utilisateur ? **Oui**
- Données collectées : identifiants de compte (email), infos personnelles (nom, prénom), infos utilisateur (surnom), activité dans l’app (matchs, entraînements, tirs)
- Données chiffrées en transit ? **Oui**
- Les utilisateurs peuvent-ils demander la suppression ? **Oui** (Paramètres dans l’app / site)
- Données vendues ? **Non**
- Données partagées avec des tiers (tracking) ? **Non** (selon le code app)
- Compte obligatoire ? **Oui**
- Publicité dans l’app ? **Non** (aucun SDK pub)
- Ciblage enfants ? **Non** dans le code
