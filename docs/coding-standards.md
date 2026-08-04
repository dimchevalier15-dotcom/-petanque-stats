# Standards de développement

## PHP

Toujours utiliser :

- readonly
- final
- enum
- constructor promotion
- propriétés typées

Interdits :

- mixed sans justification
- static utilitaires
- variables globales

## Méthodes

Maximum conseillé :

30 lignes.

## Classes

Une responsabilité.

Maximum conseillé :

300 lignes.

## Variables

Toujours explicites.

Mauvais :

```
$data
$list
$tmp
```

Bon :

```
playerRanking

tournamentResults
```

## Exceptions

Préférer des exceptions métier.

Éviter RuntimeException partout.

## Commentaires

Les commentaires expliquent "pourquoi".

Jamais "quoi".