# Pétanque Analytics

# 03 - Wireframes

**Version :** 0.1

**Statut :** En évolution

---

# Philosophie

Les wireframes décrivent uniquement :

- les informations affichées ;
- la disposition générale ;
- les interactions principales.

Ils ne définissent ni le design, ni les couleurs.

---

# 1. Connexion

```
+--------------------------------------+

            Pétanque Analytics

Email

[________________________]

Mot de passe

[________________________]

[ Connexion ]

----------------------------

Créer un compte

+--------------------------------------+
```

---

# 2. Accueil

```
+--------------------------------------+

      Pétanque Analytics

[ Nouvelle partie ]

----------------------------

[ Reprendre une partie ]

(visible uniquement si un MatchDraft existe)

+--------------------------------------+
```

---

# 3. Création d'un Match

```
+--------------------------------------+

Nouvelle partie

Format

( ) Tête-à-tête

(*) Doublette

( ) Triplette

----------------------------

Notation

(*) Standard

( ) Simple

----------------------------

Equipe A

🔍 Dimitri

[Tireur ▼]

📊 Oui

----------------------------

🔍 Alex

[Milieu ▼]

📊 Oui

----------------------------

Equipe B

🔍 Matej

[Tireur ▼]

📊 Oui

----------------------------

🔍 Michal

[Pointeur ▼]

📊 Oui

----------------------------

[ + Ajouter un joueur ]

----------------------------

[ Commencer ]

+--------------------------------------+
```

---

# Recherche d'un Player

```
+--------------------------------------+

Rechercher un joueur

[ Alex____________ ]

----------------------------

Alex Novak

Alex Horvath

Alex Kovac

----------------------------

+ Créer un nouveau joueur

+--------------------------------------+
```

---

# Création d'un Player

Fenêtre modale.

```
+--------------------------------------+

Créer un joueur

Prénom *

[____________]

Nom *

[____________]

Surnom

[____________]

Pays

[____________]

Club

[____________]

----------------------------

[ Annuler ]

[ Créer ]

+--------------------------------------+
```

---

# 4. Match

```
+--------------------------------------+

Mène 4

Score

8 - 6

----------------------------

Distance estimée

[ 8.5 ]

----------------------------

Alex

(TIR)

+2  +1  0  -1  -2

----------------------------

Dim

(TIR)

+2  +1  0  -1  -2

----------------------------

Matej

(POINT)

+2  +1  0  -1  -2

----------------------------

Michal

(POINT)

+2  +1  0  -1  -2

----------------------------

[ Valider la mène ]

+--------------------------------------+
```

---

# Validation d'une mène

```
+--------------------------------------+

Qui marque ?

(*) Equipe A

( ) Equipe B

----------------------------

Nombre de points

(0)

(1)

(2)

(3)

(4)

(5)

(6)

----------------------------

[ Valider ]

+--------------------------------------+
```

---

# 5. Fin du Match

```
+--------------------------------------+

Match terminé

Type

( ) Entraînement

( ) Amicale

(*) Compétition

----------------------------

Nom

[__________________]

----------------------------

Stade

[ Quart ▼ ]

----------------------------

Terrain

[ Galanta ▼ ]

----------------------------

Commentaire

_____________________

_____________________

----------------------------

[ Ignorer ]

[ Enregistrer ]

+--------------------------------------+
```

---

# Navigation

Connexion

↓

Accueil

↓

Création du Match

↓

Match

↓

Fin du Match

↓

Accueil

---

# Règles

Le nombre d'écrans ne doit pas augmenter sans validation.

Toute nouvelle fonctionnalité doit s'intégrer dans ces écrans si possible.

La création d'un Player est une modale.

La recherche d'un Player est une modale.

Le Match constitue l'écran principal de l'application.

Toute optimisation UX devra préserver ce principe.