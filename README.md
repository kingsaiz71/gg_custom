# Gens de Goût – Fonctions personnalisées (v1.0.0)

Extension autonome construite à partir de l'intégralité du code PHP transmis (23/10/2026). Le fichier source n'est pas modifié dans cette version.

## Installation sécurisée
1. Sauvegarder `wp-content/themes/[theme-actif]/functions.php` et le site.
2. WordPress > Extensions > Ajouter une extension > Téléverser une extension : sélectionner le ZIP et l'activer.
3. Tant que les fonctions personnalisées existent dans `functions.php`, le plugin affiche un **avertissement** et ne charge pas son propre code, afin d'éviter l'erreur PHP `Cannot redeclare`. Le code du thème continue de fonctionner.
4. Supprimer **uniquement le code personnalisé correspondant** de `functions.php` (ne pas vider les fonctions natives Divi et ne pas effacer le fichier entier). Si le fichier actif a été entièrement créé par vous, conserver au minimum un fichier PHP valide.
5. Recharger l'administration puis vérifier la disparition de l'avertissement. Purger le cache Divi/cache du site et tester les pages.
6. Vérifier particulièrement `[gdd_blog_grid category="3" posts="15" balance_chefs="true"]`, `[gdd_recipe_search]`, `[gdd_recipe_search_header]`, `[articles_aleatoires]`, `[article_aleatoire_categorie_3]` et `[gdd_share_article]`.
7. Vérifier que les valeurs Yoast sont toujours accessibles via REST pour Make, ainsi que les colonnes ACF dans Articles.

**Important :** Si une autre extension définit déjà une de ces fonctions, la même protection laisse le code de cette extension prioritaire et affiche un avertissement. Le plugin doit être chargé seul pour reproduire intégralement son comportement.

## Contenu
- `gdd-fonctions-personnalisees.php` : amorçage et contrôle des collisions.
- `includes/fonctions-originales.php` : code PHP complet fourni par l'utilisateur.

## Limites
- Le CSS hors `functions.php` (CSS personnalisé Divi, feuille de style du thème, etc.) n'est pas inclus.
- La source conserve la logique et les requêtes existantes, sans refonte des performances.
- Aucun test sur le WordPress de production n'a été effectué : tester après installation.
