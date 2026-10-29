<?php
/**
 * Plugin Name: Gens de Goût – Fonctions personnalisées
 * Description: Shortcodes recettes, recherche, partage, intégration Yoast/Make et colonnes ACF, indépendants du thème Divi.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Gens de Goût
 * Text Domain: gdd-fonctions-personnalisees
 * Update URI: https://gensdegout.fr/gdd-fonctions-personnalisees/
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Le thème est chargé après les plugins. En attendant after_setup_theme,
 * nous pouvons détecter les fonctions encore présentes dans functions.php.
 * Cela évite une erreur fatale « Cannot redeclare » pendant la migration.
 */
add_action('after_setup_theme', function () {
    $functions = [
        'gensdegout_article_aleatoire_categorie_3',
        'ba_afficher_articles_aleatoires',
        'gdd_divi_blog_image_width',
        'gdd_divi_blog_image_height',
        'gdd_share_article_shortcode',
        'gdd_grid_get_chef_name',
        'gdd_grid_balance_posts_by_chef',
        'gdd_blog_grid_shortcode',
        'gdd_recipe_search_header_shortcode',
        'gdd_get_current_recipe_category_id',
        'gdd_get_post_chef_name',
        'gdd_get_recipe_chefs',
        'gdd_recipe_search_shortcode',
        'gdd_get_acf_value',
        'gdd_is_recipe_post',
        'gdd_should_display_admin_values',
        'gdd_add_admin_post_columns',
        'gdd_render_admin_post_columns',
        'gdd_make_admin_columns_sortable',
        'gdd_apply_admin_chef_sorting',
        'gdd_add_admin_chef_filter',
        'gdd_apply_admin_chef_filter',
        'gdd_admin_columns_styles'
    ];
    $constants = [
        'GDD_RECIPES_CATEGORY_SLUG',
        'GDD_ACF_CHEF',
        'GDD_ACF_PHOTO_CHEF',
        'GDD_ACF_RESTAURANT',
        'GDD_ACF_VILLE',
        'GDD_ACF_DISTINCTION',
        'GDD_LIMIT_ADMIN_COLUMNS_TO_RECIPES'
    ];
    $conflicts = [];
    foreach ($functions as $function) {
        if (function_exists($function)) {
            $conflicts[] = $function . '()';
        }
    }
    foreach ($constants as $constant) {
        if (defined($constant)) {
            $conflicts[] = $constant;
        }
    }
    if ($conflicts) {
        add_action('admin_notices', static function () use ($conflicts) {
            if (!current_user_can('manage_options')) {
                return;
            }
            echo '<div class="notice notice-warning"><p><strong>Gens de Goût – Fonctions personnalisées :</strong> ';
            echo esc_html('L’ancien code est encore chargé (ex. ' . implode(', ', array_slice($conflicts, 0, 3)) . '). Le plugin est en attente pour éviter les doublons. Supprimez uniquement le bloc personnalisé de functions.php après sauvegarde, puis rechargez la page.');
            echo '</p></div>';
        });
        return;
    }
    require_once __DIR__ . '/includes/fonctions-originales.php';
}, 999);
