US — Le point dans les ronds (version point)

Objectif

Ajouter un exercice d'entraînement « Le point dans les ronds ».
Cette US couvre la version point. Une version tir viendra plus tard : elle réutilisera les mêmes séances et boules, distinguées par un mode (`point` aujourd'hui).

Les statistiques détaillées ne font pas partie de cette US : les données sont enregistrées pour pouvoir les calculer plus tard.

1. Terrain

Le joueur joue toujours depuis le même cercle de lancer, qui ne bouge pas.

Trois ronds de 50 cm de diamètre, un cochonnet au centre de chacun :

6,5 m — 1 point — aucun obstacle
7,5 m — 3 points — une boule posée devant le rond
8,5 m — 5 points — deux boules posées devant le rond

2. Règle

Le joueur dispose de 15 boules (15 essais) pour marquer le maximum de points.

Avant chaque boule, il choisit librement le rond visé.

Une boule est réussie si elle s'arrête dans le rond visé : elle rapporte les points de ce rond.
Sinon, elle rapporte 0 point.

Toucher une boule obstacle n'est pas une faute : seule la position finale de la boule compte.

Score maximum : 15 × 5 = 75 points.

3. Saisie

Tout se fait sur un seul écran.

Un seul appui par boule : le rond tenté et son résultat (réussi / raté).

La dernière boule saisie peut être annulée.

Les boules sont conservées localement pendant la séance, puis envoyées au backend en fin de séance (même mécanisme que le point de précision).

4. Données enregistrées

Séance : joueur, mode, dates de création / fin, score total.

Boule : séance, position (1 à 15), rond visé, réussite, score.

Le score total est la somme des scores des 15 boules.

5. Fin de séance

Après la 15e boule, le joueur termine la séance et arrive sur un récap :

score total ;
réussites par rond ;
détail des 15 boules.

Les séances terminées sont listées dans l'historique de l'exercice.
