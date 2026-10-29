# GG Custom — Gens de Goût

Plugin WordPress personnalisé du site Gens de Goût.

## Version initiale

L'archive originale `gdd-fonctions-personnalisees-v1.0.0.zip` contient le dossier `gdd-fonctions-personnalisees/`, le point d'entrée `gdd-fonctions-personnalisees.php`, le fichier `includes/fonctions-originales.php` et un `README.md`.

## Installation

Dans WordPress : **Extensions > Ajouter une extension > Téléverser une extension**, puis sélectionner l'archive ZIP correspondante.

**Attention :** ne pas réinstaller ni remplacer le plugin du site de production sans sauvegarde préalable et vérification des fonctions et shortcodes.

## Versions

Utiliser une étiquette Git `vX.Y.Z` (ex. `v1.0.0`) et publier le ZIP correspondant dans **GitHub Releases**.

Le workflow `.github/workflows/release.yml` est déclenché par une étiquette `v*` et publie l'archive ZIP dans la release. Les mises à jour automatiques sur un WordPress utilisant un dépôt privé **ne sont pas configurées** : elles nécessitent un dispositif d'authentification sécurisé côté serveur et un mécanisme de mise à jour spécifique.

## Développement

La version actuelle est conservée sous forme ZIP. Pour rendre les modifications traçables, importer ensuite les fichiers sources PHP en clair dans ce dépôt (sans supprimer l'archive d'origine).

Ne jamais committer d'identifiants, tokens GitHub ou secrets WordPress.
