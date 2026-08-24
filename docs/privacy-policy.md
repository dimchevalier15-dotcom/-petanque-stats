# Politique de confidentialité — Pétanque Stats

**Statut :** brouillon à publier en HTTPS avant la mise en ligne Play Store.  
**URL prévue après déploiement du frontend :** `https://petanque-analytics.com/privacy.html`

Ce texte reflète le comportement actuel du code. Il n’invente pas de sous-traitant. Les **TODO** doivent être complétés (identité de l’éditeur, contact).

Une copie HTML se trouve dans `mobile/public/privacy.html` (incluse dans le build Vite).

---

## TODO éditeur

- Raison sociale / nom de l’éditeur
- Adresse postale (si exigée)
- Email de contact vie privée
- Date d’entrée en vigueur

---

## Contenu proposé

Pétanque Stats est une application de suivi de parties et d’entraînements de pétanque.

### Données traitées

- Compte : adresse email, mot de passe (stocké sous forme de hash).
- Profil joueur : prénom, nom, surnom.
- Données de jeu : matchs terminés, boules et mènes, séances d’entraînement, séances de tir de précision.
- Sur l’appareil : jeton de session, langue, brouillons de match ou de tir non encore envoyés.

Pas de publicité in-app, pas de kit d’analyse ou de crash reporting dans l’application.

### Finalité

Fournir le compte, enregistrer les performances et afficher des statistiques calculées à partir des actions de jeu.

### Destinataires

Les données sont stockées sur le serveur d’application (`api.petanque-analytics.com`). Elles ne sont pas revendues. Google Play peut traiter des données de distribution si l’app est installée via le Play Store.

### Conservation

Les données de compte et de jeu restent tant que le compte existe. Vous pouvez supprimer votre compte dans l’application : **Paramètres → Supprimer mon compte** (également sur `https://petanque-analytics.com/settings` après connexion). L’email et le mot de passe sont effacés. Le profil joueur peut rester dans l’historique des matchs, sans lien avec un compte.

### Transfert

Les échanges app ↔ API utilisent HTTPS.

### Droits

Selon la loi applicable (ex. RGPD), accès, rectification, opposition, limitation, portabilité, suppression. La suppression s’effectue dans l’app (Paramètres). **TODO :** email de contact pour les autres demandes.

### Contact

**TODO :** email.
