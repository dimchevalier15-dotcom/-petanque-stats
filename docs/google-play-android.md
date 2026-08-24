# Publication Google Play (Android)

**Statut :** préparation du premier AAB  
**Application ID :** `com.petanquestats.app` (définitif, ne pas modifier)

Ce document décrit la préparation Android pour Google Play. Il ne remplace pas `docs/deployment.md` pour le backend.

---

## Versions Android

| Champ | Valeur première release |
| --- | --- |
| `versionName` | `1.0.0` (visible sur Play Store) |
| `versionCode` | `1` (entier interne Google Play) |

Source : `mobile/android/app/build.gradle` (`defaultConfig`).

### Incrément pour les releases suivantes

1. Chaque upload Play Console **doit** avoir un `versionCode` **strictement supérieur** au précédent. Ne jamais le diminuer.
2. Incrémenter `versionCode` de 1 à chaque AAB envoyé (2, 3, 4…).
3. Faire évoluer `versionName` selon semver produit :
   - correctif : `1.0.1`
   - fonctionnalité : `1.1.0`
   - rupture : `2.0.0`
4. Aligner `mobile/package.json` `version` avec `versionName` lorsque c’est pratique, mais **seul Gradle compte** pour Play.

---

## SDK Android

Exigence Google Play au 20 août 2026 : une **nouvelle** application doit cibler **API 35** jusqu’au 30 août 2026, puis **API 36** à partir du **31 août 2026**.

Capacitor lie le `targetSdk` à sa version majeure. Cibler 36 impose **Capacitor 8**.

| Champ | Valeur |
| --- | --- |
| `minSdk` | 24 (Android 7.0) |
| `compileSdk` | 36 |
| `targetSdk` | 36 |
| Capacitor | 8.x |

Galaxy S25 (Android récent) est compatible. Les appareils sous Android 6 et moins ne le sont plus (`minSdk` 24, exigence Capacitor 8).

JDK Gradle : **21** (Capacitor 8 compile en Java 21). Exemple Homebrew `openjdk@21` :

```
export JAVA_HOME="/opt/homebrew/opt/openjdk@21/libexec/openjdk.jdk/Contents/Home"
```

Éviter le JBR 25 d’Android Studio si Gradle échoue.

---

## HTTPS / API

Le build `vite build` (mode production) lit `mobile/.env.production` :

```
VITE_API_URL=https://api.petanque-analytics.com/api
```

Android n’utilise pas le proxy Vite. Le trafic HTTP clair est interdit (`android:usesCleartextTraffic="false"`).

---

## Commandes de build

Depuis `mobile/` :

```
npm run build
npx cap sync android
```

Puis génération de l’AAB (équivalent `npm run android:bundle:gradle`) :

```
cd android
./gradlew bundleRelease
```

Enchaînement : `npm run android:bundle`.

Fichier produit :

```
mobile/android/app/build/outputs/bundle/release/app-release.aab
```

Ce chemin est gitignoré (`*.aab`). Copier l’AAB hors du dossier `build/` avant un `clean`.

Prérequis signature : `mobile/android/keystore.properties` et le fichier `.jks` pointé par `storeFile`.

---

## Signature (upload key)

Google Play App Signing **reste activé** (défaut Play Console). Ne pas le désactiver.

| Clé | Qui la détient | Rôle |
| --- | --- | --- |
| **Clé d’upload** | vous, en local | Signe l’AAB avant upload |
| **Clé de signature d’app** | Google | Signe ce que les utilisateurs installent |

### Créer la clé d’upload (une seule fois)

Ne jamais committer le keystore ni les mots de passe.

```
cd mobile/android
cp keystore.properties.example keystore.properties
mkdir -p keystore
keytool -genkeypair -v \
  -keystore keystore/upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias upload
```

Renseigner `storePassword`, `keyAlias` (`upload`) et `keyPassword` dans `keystore.properties`.

### Sauvegarde

Conserver hors Git, en au moins deux endroits sûrs :

- `keystore/upload-keystore.jks`
- `keystore.properties`

Sans la clé d’upload, un nouvel AAB ne pourra plus être accepté (sauf reset d’upload key via Play Console, procédure lourde).

### Recréer un AAB plus tard

1. Restaurer le même `.jks` et le même `keystore.properties`.
2. Incrémenter `versionCode` / `versionName`.
3. `npm run android:bundle`.
4. Uploader le nouvel `app-release.aab`.

---

## Splash et icône

- Source icône (ne pas modifier) : `mobile/resources/icon.png`
- Génération Capacitor : `npm run assets:android` (`@capacitor/assets`)
- Fond splash / adaptive icon : `#1F6B58` (couleur primaire)
- Il n’y a pas d’asset splash distinct : le splash Android est généré à partir de l’icône et du fond.

---

## Permissions

Déclarée dans `AndroidManifest.xml` :

| Permission | Pourquoi | Fonctionnalité | Play Console |
| --- | --- | --- | --- |
| `INTERNET` | Appeler l’API HTTPS | Auth, matchs, entraînement, stats | Réseau, attendue ; pas une permission « sensible » type localisation |

Ajoutée au merge par AndroidX (protection interne, niveau `signature`) :

| Permission | Pourquoi | Play Console |
| --- | --- | --- |
| `com.petanquestats.app.DYNAMIC_RECEIVER_NOT_EXPORTED_PERMISSION` | Récepteurs AndroidX (profile installer). Pas d’accès utilisateur. | Rien à demander à l’utilisateur |

Pas de localisation, caméra, micro, contacts, stockage, notifications.

Le `FileProvider` Capacitor est présent (partage de fichiers) mais aucun plugin Share/Camera n’est utilisé.

---

## Checklist Google Play Console (actions manuelles)

Ne pas inventer les textes marketing. Compléter les TODO avant soumission.

| Élément | Statut |
| --- | --- |
| Nom de l’application | **Pétanque Stats** (aligné Capacitor / `strings.xml`) |
| Description courte | **TODO** (max 80 caractères, rédiger) |
| Description complète | **TODO** (rédiger, 3 langues éventuelles fr/en/sk) |
| Catégorie | **TODO** (proposition à valider : Sports) |
| Coordonnées / email de contact | **TODO** |
| Icône Play (512×512) | Générer depuis `mobile/resources/icon.png` (asset haute résolution) |
| Screenshots téléphone | **TODO** (prendre sur Galaxy S25, 2 à 8 images) |
| Politique de confidentialité (URL HTTPS publique) | **TODO** — contenu : `docs/privacy-policy.md`. Fichier web prêt : `mobile/public/privacy.html` → après déploiement front : `https://petanque-analytics.com/privacy.html` |
| Classification du contenu | **TODO** (questionnaire IARC dans la console) |
| Data Safety | Remplir d’après `docs/google-play-data-safety.md` |
| Publicité | **TODO** à confirmer : l’app n’intègre **pas** de SDK pub |
| Audience cible | **TODO** (proposition : public sportif adulte ; le code ne cible pas les enfants) |
| Pays de distribution | **TODO** |
| Fiche développeur / compte Play | **TODO** (compte payant Google Play Console) |
| Suppression de compte | In-app : Paramètres. URL web : `https://petanque-analytics.com/settings` |

---

## Ce que cette préparation ne fait pas

- Aucune publication Play Console
- Aucune modification Symfony / MySQL / Docker / Caddy / DNS
- Aucune clé de signature dans Git
