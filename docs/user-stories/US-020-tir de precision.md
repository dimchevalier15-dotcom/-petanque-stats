US — Ajouter le mode Tir de précision
AI Rules — obligatoire

Avant toute modification, relire intégralement les AI Rules du projet dans docs/06-ai-rules.md

Respecter strictement l'architecture existante.

En particulier :

ne pas modifier les règles métier existantes ;
ne pas casser le fonctionnement des matchs ;
TypeScript strict, aucun any ;
respecter la séparation Models / DTO / Services / Repositories ;
toutes les chaînes visibles passent par i18n ;
ne pas introduire de nouvelle dépendance sans nécessité ;
réutiliser les composants et patterns existants lorsqu'ils sont pertinents ;
ne pas dupliquer inutilement de logique ;
ne pas modifier les statistiques de matchs existantes ;
les statistiques du tir de précision doivent rester complètement séparées des statistiques de matchs.

Si une décision technique est nécessaire, privilégier la solution simple, cohérente avec l'architecture actuelle et facilement extensible.

Objectif

Ajouter un nouveau mode d'utilisation de l'application :

Tir de précision

Un joueur peut réaliser une séance complète de tir de précision.

Une séance est toujours associée à un Player.

Un même joueur peut réaliser autant de séances qu'il le souhaite.

Chaque séance doit être conservée.

Les données de tir de précision doivent être indépendantes des matchs et de leurs statistiques.

1. Accès depuis le menu

Ajouter une nouvelle entrée dans le menu principal :

Tir de précision

L'entrée doit être visuellement cohérente avec les autres actions du menu.

Elle ouvre le nouvel espace de séances de tir de précision.

Ne pas modifier la navigation existante des matchs.

2. Démarrer une séance

Lorsqu'un utilisateur démarre une séance :

identifier le Player concerné ;
créer une nouvelle séance de tir de précision ;
commencer à l'atelier 1 ;
afficher les 4 distances de l'atelier.

La séance doit être persistée.

Un joueur peut avoir autant de séances qu'il le souhaite :

Séance 1
Séance 2
Séance 3
...

Aucune limite sur le nombre de séances.

3. Modèle métier

Créer une entité représentant une séance de tir de précision.

Elle doit être liée à :

Player

et contenir au minimum :

identifiant ;
player ;
date/heure de création ;
date/heure de fin si pertinent ;
statut permettant de distinguer une séance en cours d'une séance terminée si nécessaire ;
score total calculable.

Créer également une entité représentant chaque tir individuel.

Un tir doit être lié à sa séance et permettre de connaître précisément :

la séance ;
l'atelier ;
la distance ;
le résultat du tir ;
le score obtenu.

Le modèle doit permettre de retrouver exactement les 20 tirs d'une séance complète.

4. Structure officielle

Le tir de précision comporte 5 ateliers :

Boule seule
Boule derrière but
Entre deux boules
Sautée
But

Les distances sont :

6 m
7 m
8 m
9 m

Chaque atelier comporte donc 4 tirs.

Soit :

5 ateliers × 4 distances = 20 tirs par séance.

Cette structure doit être modélisée explicitement.

Ne pas simplement stocker un score total de séance.

5. Données d'un tir

Chaque tir individuel doit être enregistré.

Il doit notamment être possible de répondre à :

Quel résultat ce joueur a-t-il obtenu à l'atelier 3 à 8 mètres lors de cette séance ?

Et :

Combien de fois ce joueur a-t-il réussi l'atelier 2 à 7 mètres sur l'ensemble de ses séances ?

Les données doivent donc permettre des statistiques futures par :

joueur ;
séance ;
atelier ;
distance ;
résultat ;
score.
6. Résultat d'un tir

Utiliser le barème officiel du tir de précision.

Les résultats doivent permettre de distinguer au minimum :

manqué ;
touché ;
réussi ;
carreau.

Barème de base :

0 point — manqué
1 point — touché
3 points — réussi
5 points — carreau

Le cinquième atelier possède ses propres règles de comptage du but : respecter le barème officiel plutôt que de supposer que tous les ateliers utilisent exactement la même interprétation.

Ne pas inventer de nouvelles catégories de résultat.

Centraliser les valeurs dans le modèle métier plutôt que de disperser des nombres magiques dans le frontend.

7. UX du déroulement

Je veux une UX proche de celle du déroulement d'un match.

La fonctionnalité doit être pensée pour être utilisée :

debout ;
sur un terrain ;
téléphone en main ;
avec une saisie extrêmement rapide.

Le design doit être cohérent avec l'identité visuelle actuelle de l'application.

Mais il ne faut pas simplement copier l'écran de match.

Créer une interface dédiée au tir de précision.

8. Un écran par atelier

La séance se déroule atelier par atelier.

Exemple :

Atelier 1
Boule seule

6 m
7 m
8 m
9 m

Les 4 distances de l'atelier doivent être visibles simultanément.

Chaque distance correspond à une seule boule.

L'utilisateur doit pouvoir enregistrer très rapidement le résultat de chaque boule.

9. Saisie d'une boule

Pour chaque distance, proposer une interaction très rapide permettant de sélectionner son résultat.

L'utilisateur doit clairement voir les quatre tirs :

6 m    [ résultat ]
7 m    [ résultat ]
8 m    [ résultat ]
9 m    [ résultat ]

Une fois le résultat sélectionné, il doit être immédiatement évident que le tir est enregistré.

Le score correspondant doit être visible.

Ne pas demander une validation complexe pour chaque boule.

10. Navigation entre ateliers

Après les 4 tirs :

Atelier 1
    ↓
Atelier 2
    ↓
Atelier 3
    ↓
Atelier 4
    ↓
Atelier 5
    ↓
Fin de séance

L'utilisateur doit savoir clairement :

où il en est ;
combien d'ateliers restent ;
son score actuel.

Afficher une indication de progression élégante, par exemple :

Atelier 3 / 5
11. Score

Afficher :

le score de l'atelier courant ;
le score cumulé de la séance.

Le score total doit être calculé à partir des tirs enregistrés.

Ne pas considérer le score total comme la donnée source.

Les tirs individuels sont la source de vérité.

12. Fin de séance

Après le dernier tir de l'atelier 5, afficher un écran de résumé de séance.

Il doit présenter au minimum :

score total ;
score par atelier ;
score par distance ;
détail des résultats si pertinent.

Le design doit être cohérent avec la page de résumé des matchs, tout en ayant sa propre identité.

La séance est alors considérée comme terminée.

13. Persistance

Chaque tir doit être sauvegardé.

Ne pas attendre uniquement la fin de la séance pour envoyer les 20 résultats au backend.

L'objectif est de limiter la perte de données si :

le navigateur est fermé ;
le téléphone perd temporairement la connexion ;
l'utilisateur quitte accidentellement la page.

Utiliser l'architecture existante du projet pour gérer cette persistance.

Si le projet dispose déjà d'un mécanisme adapté, le réutiliser.

Ne pas construire pour cette US un système offline complexe.

14. Reprise d'une séance

Si une séance commencée mais non terminée existe, réfléchir à la meilleure UX pour permettre sa reprise.

L'utilisateur ne doit pas perdre une séance en cours simplement parce qu'il quitte temporairement l'écran.

Si une solution simple et cohérente existe dans l'architecture actuelle, l'implémenter.

Ne pas construire un système offline complet pour cette fonctionnalité.

15. Statistiques séparées

IMPORTANT :

Les statistiques de tir de précision sont totalement séparées des statistiques de matchs.

Ne pas mélanger :

Statistiques de matchs

et :

Statistiques de tir de précision

Créer une structure permettant plus tard de calculer notamment :

meilleur score ;
moyenne par séance ;
moyenne par atelier ;
moyenne par distance ;
taux de réussite ;
progression dans le temps ;
score moyen à 6 m ;
score moyen à 7 m ;
score moyen à 8 m ;
score moyen à 9 m ;
performance sur chaque atelier.

Cette US doit surtout enregistrer correctement les données nécessaires.

Il n'est pas nécessaire de construire toute la page de statistiques avancées maintenant.

16. Historique des séances

Prévoir une manière cohérente d'accéder aux séances précédentes du joueur.

Une séance doit pouvoir être retrouvée à partir du Player.

Afficher au minimum :

date ;
score total ;
éventuellement un résumé court.

Au clic, afficher le résumé détaillé de la séance.

L'objectif est que toutes les séances soient conservées et accessibles.

17. Sécurité et accès

Un utilisateur ne doit pouvoir consulter ou modifier que les séances des Player auxquels il a accès selon les règles existantes de l'application.

Ne jamais faire confiance à un playerId fourni uniquement par le frontend.

Les contrôles d'autorisation doivent être effectués côté backend.

18. Internationalisation

Toutes les chaînes visibles doivent passer par i18n.

Ajouter les traductions dans toutes les langues actuellement supportées.

Prévoir notamment les traductions pour :

Tir de précision ;
Séance ;
Atelier ;
Boule seule ;
Boule derrière but ;
Entre deux boules ;
Sautée ;
But ;
6 m ;
7 m ;
8 m ;
9 m ;
Manqué ;
Touché ;
Réussi ;
Carreau ;
Score ;
Séance terminée ;
progression ;
actions de navigation.

Ne pas mettre de texte français directement dans les composants.

19. Responsive / mobile

La priorité absolue est le téléphone.

Tester au minimum les largeurs :

320 px
375 px
390 px
430 px

Les 4 tirs de l'atelier doivent être faciles à manipuler sans donner l'impression d'une grille minuscule.

Tu peux utiliser des cartes, listes, boutons, segments ou tout autre composant adapté.

Choisis la meilleure solution UX.

Évite une interface trop dense.

20. Direction artistique

Je veux que cette fonctionnalité ait le même niveau de finition que le déroulement de match.

Elle doit être :

sportive ;
moderne ;
claire ;
rapide ;
premium ;
agréable à utiliser pendant une séance d'entraînement.

Tu peux être créatif sur :

la représentation des ateliers ;
la progression ;
les résultats ;
les distances ;
le score ;
les animations légères ;
le résumé.

Mais ne sacrifie jamais la rapidité de saisie.

21. Architecture

Avant de coder :

lire les AI Rules ;
lire docs/ ;
examiner les modèles Player ;
examiner les relations User / Player ;
examiner l'architecture des matchs ;
examiner comment les End et Ball sont persistés ;
examiner les services et repositories existants ;
examiner le système d'autorisation ;
examiner le système i18n ;
examiner les composants UI existants.

Réutiliser les conventions du projet.

Ne pas copier-coller l'architecture des matchs si une abstraction plus adaptée est évidente.

22. Tests

Ajouter les tests pertinents.

Au minimum :

Création
une séance appartient à un Player ;
un Player peut avoir plusieurs séances.
Tirs
un tir appartient à une séance ;
un tir possède un atelier ;
un tir possède une distance ;
un tir possède un résultat ;
un tir possède un score.
Structure

Une séance complète contient :

5 ateliers
4 distances par atelier
20 tirs
Score

Vérifier que le score total correspond à la somme des tirs.

Sécurité

Un utilisateur ne peut pas accéder à la séance d'un Player auquel il n'a pas accès.

Matchs

Vérifier que l'ajout du tir de précision ne modifie pas les statistiques ou le fonctionnement des matchs existants.

23. Ce qui n'est PAS demandé

Ne pas développer maintenant :

classement de tir de précision ;
comparaison publique entre joueurs ;
compétition de tir ;
matchmaking ;
statistiques sociales ;
système de records publics ;
coaching automatique ;
analyse vidéo ;
détection automatique du résultat ;
mode compétition officiel complet.

Le but est simplement :

permettre à un joueur de faire une séance complète de tir de précision, enregistrer chaque tir correctement et conserver son historique.

Résultat attendu

Depuis le menu :

Tir de précision
       ↓
Nouvelle séance
       ↓
Atelier 1 — 4 tirs
       ↓
Atelier 2 — 4 tirs
       ↓
Atelier 3 — 4 tirs
       ↓
Atelier 4 — 4 tirs
       ↓
Atelier 5 — 4 tirs
       ↓
Résumé
       ↓
Séance enregistrée

Chaque tir doit être identifiable individuellement par :

Player
+ séance
+ atelier
+ distance
+ résultat
+ score

C'est la donnée fondamentale de cette fonctionnalité.

Le reste de l'UX peut être imaginé librement, dans le style actuel de l'application.