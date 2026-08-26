# Google Play Console — Pétanque Analytics

Ce document prépare la première publication. Il ne garantit pas à lui seul une conformité juridique ou une validation par Google.

Détails Android (SDK, signature, AAB) : `docs/google-play-android.md`  
Analyse Data Safety basée sur le code : `docs/google-play-data-safety.md`

**Ne pas déployer, modifier le VPS, le DNS ou les secrets à partir de ce fichier.**

---

## URLs à renseigner dans Play Console

À utiliser **après déploiement du frontend** (les routes existent dans l’application Vue ; nginx sert déjà `index.html` pour les chemins inconnus).

| Champ Play Console | URL |
| --- | --- |
| Politique de confidentialité | https://petanque-analytics.com/privacy |
| Conditions d’utilisation (si demandées) | https://petanque-analytics.com/terms |
| Suppression de compte (URL web) | https://petanque-analytics.com/delete-account |
| Mentions légales (pas un champ Play obligatoire) | https://petanque-analytics.com/legal |

Ancienne URL statique `https://petanque-analytics.com/privacy.html` : redirection vers `/privacy`.

Ces pages sont publiques (aucun compte requis pour les lire). La suppression effective exige une connexion, afin que seul le titulaire du compte puisse le supprimer.

---

## Checklist Play Console

### Fiche de l’application

| Élément | Valeur / action |
| --- | --- |
| Nom affiché | **Pétanque Analytics** (Capacitor, Android, UI i18n) |
| Package name (`applicationId`) | `com.petanquestats.app` — **ne pas modifier** après la première publication |
| `versionName` | `1.0.0` (`mobile/android/app/build.gradle`) |
| `versionCode` | `1` — incrémenter à chaque nouvel AAB |
| E-mail de contact développeur | dimchevalier15@gmail.com |
| Site web | https://petanque-analytics.com |
| Catégorie | **TODO** (proposition : Sports) |
| Description courte / complète | **TODO** |
| Icône 512×512 | `mobile/resources/play-icon-512.png` — uploader dans Play Console |
| Captures d’écran | **TODO** (2 à 8, téléphone, prises sur appareil) |
| Classification du contenu (IARC) | **TODO** questionnaire dans la console |
| Pays de distribution | **TODO** |
| Publicité dans l’app | Non (aucun SDK pub dans le code) |
| Audience | **TODO** — le code ne cible pas les enfants |
| Compte Play Console payant | **TODO** (action manuelle) |

### Compte et accès testeur

L’application exige un compte (e-mail + mot de passe). Google peut demander un **compte de démonstration** pour examiner les zones authentifiées.

| Élément | Action |
| --- | --- |
| Compte de démo | **TODO** — créer un compte dédié (e-mail + mot de passe) et le renseigner dans Play Console si demandé |
| Vérification d’e-mail | Un compte de démo déjà vérifié évite un blocage du reviewer |
| Données de démo | Quelques matchs / joueurs aident à voir les écrans principaux |

### Signature et AAB

| Élément | Statut |
| --- | --- |
| Android App Bundle `.aab` | `npm run android:bundle` depuis `mobile/` → `mobile/android/app/build/outputs/bundle/release/app-release.aab` |
| Clé d’upload | `mobile/android/keystore/` + `keystore.properties` — **hors Git** |
| Play App Signing | Laisser activé (défaut) |

---

## Data Safety — ne pas remplir à l’aveugle

Le formulaire Play évolue. Les lignes ci-dessous sont une **lecture du code actuel**, à revérifier dans la console au moment de la soumission.

### Collecte

L’app **collecte** des données utilisateur (compte obligatoire).

| Type Play (indicatif) | Collecté ? | Détail dans le code |
| --- | --- | --- |
| Adresse e-mail | Oui | `users.email` — compte, e-mails transactionnels |
| Mot de passe | Oui (saisi) | hash uniquement en base (`users.password`) |
| Nom | Oui | prénom / nom / surnom du joueur lié |
| Activité dans l’app | Oui | matchs, entraînements, tir de précision |
| Identifiants de compte | Oui | e-mail + JWT de session côté appareil |
| Infos de paiement | Non | aucune monétisation |
| Localisation | Non | pas de plugin Geolocation |
| Photos / contacts / micro | Non | pas de plugins concernés |
| Identifiant publicitaire | Non | pas de SDK pub |
| Données de santé | Non | hors périmètre |

Les statistiques affichées sont **calculées** à la lecture ; elles ne sont pas une table séparée.

### Finalités (indicatif)

- Fonctionnalité de l’application (compte, stats de jeu)
- Gestion du compte (vérification d’e-mail, mot de passe oublié)

Pas de publicité, pas d’analytics produit, pas de personnalisation publicitaire dans le code.

### Partage / vente

| Question | Lecture du code |
| --- | --- |
| Vente des données | Non |
| SDK analytics / crash / ads | Non |
| Resend | Oui — e-mails transactionnels (adresse e-mail + lien) |
| Hetzner | Oui — hébergement VPS (API, MySQL, frontend) |
| Google Play | Possible hors app (distribution, App Signing) si l’app est installée via le Store |

Resend et l’hébergeur sont des **sous-traitants / prestataires**, pas des SDK dans l’APK. Dans Data Safety, distinguer « collecté par l’app » et « traité côté serveur ». Relire les définitions Google au moment du remplissage.

### Sécurité et suppression

| Question | Lecture du code |
| --- | --- |
| Chiffré en transit | Oui (HTTPS, `usesCleartextTraffic=false`) |
| Chiffré au repos | Non revendiqué (MySQL sur le VPS, sans preuve de chiffrement disque dans le repo) |
| Suppression demandable | Oui — in-app Paramètres, et https://petanque-analytics.com/delete-account |
| Compte obligatoire | Oui |

### Suppression : effet réel

Supprimé : e-mail, hash du mot de passe, jetons `auth_tokens` (vérification / reset), validité du JWT (utilisateur introuvable).

Conservé (volontairement, `onDelete: SET NULL` / `RESTRICT`) :

- profil joueur (prénom, nom, surnom), détaché du compte ;
- matchs, participants, boules, entraînements, séances de tir liés à ce joueur.

Raison : ne pas casser l’historique des matchs impliquant d’autres joueurs. Pas de cascade `REMOVE` sur `Player`.

---

## Réponses à vérifier avant soumission

À cocher dans la console, **en relisant le formulaire du jour** :

1. L’app collecte-t-elle des données utilisateur ? **Oui**
2. Types : e-mail, nom (joueur), activité dans l’app (matchs / entraînements / tir)
3. Collecte obligatoire pour le compte : **Oui** (e-mail, mot de passe)
4. Données vendues ? **Non** (selon le code)
5. Publicité dans l’app ? **Non** (selon le code)
6. Chiffrement en transit ? **Oui**
7. Les utilisateurs peuvent-ils demander la suppression ? **Oui**
8. URL de suppression : `https://petanque-analytics.com/delete-account`
9. URL de confidentialité : `https://petanque-analytics.com/privacy`
10. Compte de démo fourni si Google le demande ?
11. Package name = `com.petanquestats.app` ?
12. `versionCode` strictement supérieur au précédent upload ?
13. AAB signé avec la **même** clé d’upload ?
14. Placeholders légaux (`[À COMPLÉTER …]`) remplacés sur `/legal`, `/privacy`, `/terms` ?
15. E-mail de contact Play Console renseigné ?
16. Nom affiché = **Pétanque Analytics** partout (Store, Android, UI)
17. Ne pas déclarer un SDK (Firebase, GA, Sentry, ads) qui n’est pas dans `mobile/package.json`
18. Ne pas déclarer de paiement / pub / tracking s’ils n’ont pas été ajoutés depuis cette analyse

Si le code change (analytics, pub, paiement, nouveaux champs User), **refaire cette analyse** avant de resoumettre Data Safety.

---

## Routes frontend (SPA)

Fichier `mobile/nginx.prod.conf` :

```
location / {
    try_files $uri $uri/ /index.html;
}
```

Les URLs `/privacy`, `/terms`, `/legal`, `/delete-account` fonctionnent :

- navigation interne Vue Router ;
- accès direct et rafraîchissement (fallback `index.html`).

Aucun changement Caddy n’est nécessaire pour ces routes.
