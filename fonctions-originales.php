<?php
/** Code métier repris du functions.php transmis le 23 octobre 2026. */
//
//MON CODE
// IYOAST+MAKE
add_action('init', function () {
    $yoast_fields = [
        '_yoast_wpseo_title',
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_focuskw',
    ];

    foreach ($yoast_fields as $field) {
        register_post_meta('post', $field, [
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
});

/**
 * Shortcode : [article_aleatoire_categorie_3]
 *
 * Affiche un article choisi aléatoirement dans la catégorie ID 3 :
 * - titre ;
 * - extrait ;
 * - bouton vers l'article.
 */
function gensdegout_article_aleatoire_categorie_3() {

    $article_aleatoire = new WP_Query(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => 1,
            'category__in'        => array( 3 ),
            'orderby'             => 'rand',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        )
    );

    if ( ! $article_aleatoire->have_posts() ) {
        return '';
    }

    $article_aleatoire->the_post();

    $titre    = get_the_title();
    $lien     = get_permalink();
    $extrait  = get_the_excerpt();

    // Limite l'extrait à 30 mots.
    $extrait = wp_trim_words(
        wp_strip_all_tags( $extrait ),
        30,
        '…'
    );

    ob_start();
    ?>

    <article class="article-aleatoire">
        
        <h3 class="article-aleatoire__titre">
            <?php echo esc_html( $titre ); ?>
        </h3>

        <div class="article-aleatoire__extrait">
            <p><?php echo esc_html( $extrait ); ?></p>
        </div>

        <div class="article-aleatoire__bouton">
            <a 
                href="<?php echo esc_url( $lien ); ?>"
                class="et_pb_button bt_recette_jour"
                aria-label="<?php echo esc_attr( 'Regarder : ' . $titre ); ?>"
            >
                Regarder
            </a>
        </div>

    </article>

    <?php

    $contenu = ob_get_clean();

    wp_reset_postdata();

    return $contenu;
}

add_shortcode(
    'article_aleatoire_categorie_3',
    'gensdegout_article_aleatoire_categorie_3'
);

/**
 * Shortcode WordPress :
 * [articles_aleatoires categorie="3" nombre="4" image_position="center"]
 * [articles_aleatoires categorie="3,6,12" nombre="4" image_position="top"]
 *
 * Paramètres :
 * - categorie : un ou plusieurs IDs de catégories séparés par des virgules
 * - nombre : nombre d'articles à afficher
 * - mots : nombre de mots dans l'extrait
 * - image_position : top, center ou bottom
 *
 * Affiche plusieurs articles aléatoires sous forme de cartes.
 */
function ba_afficher_articles_aleatoires( $atts ) {

    $atts = shortcode_atts(
        array(
            'categorie'      => '3',
            'nombre'         => 4,
            'mots'           => 32,
            'image_position' => 'center',
        ),
        $atts,
        'articles_aleatoires'
    );

    /**
     * Gestion de plusieurs catégories :
     * categorie="3,6,12"
     */
    $categories_ids = array_filter(
        array_map(
            'absint',
            explode( ',', $atts['categorie'] )
        )
    );

    $nombre      = absint( $atts['nombre'] );
    $nombre_mots = absint( $atts['mots'] );

    /**
     * Position de l'image.
     */
    $positions_autorisees = array(
        'top',
        'center',
        'bottom',
    );

    $image_position = sanitize_key( $atts['image_position'] );

    if ( ! in_array( $image_position, $positions_autorisees, true ) ) {
        $image_position = 'center';
    }

    /**
     * Limites de sécurité.
     */
    $nombre      = max( 1, min( 12, $nombre ) );
    $nombre_mots = max( 10, min( 80, $nombre_mots ) );

    /**
     * Si aucune catégorie valide n'est fournie.
     */
    if ( empty( $categories_ids ) ) {
        return '<p class="ba-random-posts__empty">Aucune catégorie valide.</p>';
    }

    /**
     * Requête WordPress.
     *
     * category__in signifie :
     * catégorie 3 OU catégorie 6 OU catégorie 12.
     */
    $requete = new WP_Query(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $nombre,
            'category__in'        => $categories_ids,
            'orderby'             => 'rand',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        )
    );

    if ( ! $requete->have_posts() ) {
        return '<p class="ba-random-posts__empty">Aucun article disponible.</p>';
    }

    ob_start();
    ?>

    <div
        class="ba-random-posts ba-random-posts--image-<?php echo esc_attr( $image_position ); ?>"
    >

        <?php while ( $requete->have_posts() ) : ?>

            <?php
            $requete->the_post();

            $article_id = get_the_ID();
            $titre      = get_the_title();
            $lien       = get_permalink();

            /**
             * Extrait.
             */
            $extrait = get_the_excerpt();

            if ( empty( trim( $extrait ) ) ) {
                $extrait = get_the_content();
            }

            $extrait = wp_trim_words(
                wp_strip_all_tags(
                    strip_shortcodes( $extrait )
                ),
                $nombre_mots,
                '…'
            );

            /**
             * Recherche de la catégorie de l'article
             * correspondant aux catégories demandées.
             */
            $categories_article = get_the_category( $article_id );

            $nom_categorie = '';
            $lien_categorie = '';

            if ( ! empty( $categories_article ) ) {

                foreach ( $categories_article as $categorie_article ) {

                    if (
                        in_array(
                            (int) $categorie_article->term_id,
                            $categories_ids,
                            true
                        )
                    ) {

                        $nom_categorie  = $categorie_article->name;
                        $lien_categorie = get_category_link(
                            $categorie_article->term_id
                        );

                        break;
                    }
                }

                /**
                 * Fallback :
                 * si aucune catégorie ne correspond exactement
                 * aux catégories demandées, on prend la première.
                 */
                if ( empty( $nom_categorie ) ) {

                    $premiere_categorie = $categories_article[0];

                    $nom_categorie = $premiere_categorie->name;

                    $lien_categorie = get_category_link(
                        $premiere_categorie->term_id
                    );
                }
            }
            ?>

            <article class="ba-random-card">

                <div class="ba-random-card__image">

                    <a
                        href="<?php echo esc_url( $lien ); ?>"
                        class="ba-random-card__image-link"
                        aria-label="<?php echo esc_attr(
                            'Découvrir l’article : ' . $titre
                        ); ?>"
                    >

                        <?php if ( has_post_thumbnail( $article_id ) ) : ?>

                            <?php
                            echo get_the_post_thumbnail(
                                $article_id,
                                'large',
                                array(
                                    'class'    => 'ba-random-card__thumbnail',
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                )
                            );
                            ?>

                        <?php else : ?>

                            <span
                                class="ba-random-card__placeholder"
                                aria-hidden="true"
                            ></span>

                        <?php endif; ?>

                    </a>

                </div>

                <div class="ba-random-card__content">

                    <h3 class="ba-random-card__title">

                        <a href="<?php echo esc_url( $lien ); ?>">

                            <?php echo esc_html( $titre ); ?>

                        </a>

                    </h3>

                    <?php if ( $nom_categorie ) : ?>

                        <div class="ba-random-card__category">

                            <a
                                href="<?php echo esc_url(
                                    $lien_categorie
                                ); ?>"
                            >

                                <?php echo esc_html(
                                    $nom_categorie
                                ); ?>

                            </a>

                        </div>

                    <?php endif; ?>

                    <div class="ba-random-card__excerpt">

                        <p>
                            <?php echo esc_html( $extrait ); ?>
                        </p>

                    </div>

                    <!-- <div class="ba-random-card__button-wrapper">

                        <a
                            href="<?php // echo esc_url( $lien ); ?>"
                            class="bt_recette_jour et_pb_button"
                            aria-label="<?php //echo esc_attr(
                                //'Regarder : ' . $titre
                            //); ?>"
                        >
                            Regarder
                        </a>

                    </div> --!>

                </div>

            </article>

        <?php endwhile; ?>

    </div>

    <?php

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode(
    'articles_aleatoires',
    'ba_afficher_articles_aleatoires'
);


/**
 * Taille des images Divi Blog.
 */
function gdd_divi_blog_image_width( $width ) {
    return 800;
}
add_filter(
    'et_pb_blog_image_width',
    'gdd_divi_blog_image_width'
);

function gdd_divi_blog_image_height( $height ) {
    return 500;
}
add_filter(
    'et_pb_blog_image_height',
    'gdd_divi_blog_image_height'
);

 /* Shortcode : [gdd_share_article]
 *
 * Apartage sur les reseaux
 */

function gdd_share_article_shortcode() {
    if (!is_singular()) {
        return '';
    }

    $url   = urlencode(get_permalink());
    $title = urlencode(get_the_title());

    ob_start();
    ?>

    <div class="gdd-share-article" aria-label="Partager cet article">
        <span class="gdd-share-label">Partager</span>

        <nav class="gdd-share-links">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr($url); ?>"
               target="_blank"
               rel="noopener"
               aria-label="Partager sur Facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.84c0-2.52 1.49-3.91 3.77-3.91 1.09 0 2.23.2 2.23.2v2.47h-1.26c-1.24 0-1.63.78-1.63 1.57v1.89h2.78l-.44 2.91h-2.34V22C18.34 21.24 22 17.08 22 12.06Z"/>
                </svg>
                <span>Facebook</span>
            </a>

            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo esc_attr($url); ?>"
               target="_blank"
               rel="noopener"
               aria-label="Partager sur LinkedIn">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4.98 3.5C4.98 4.88 3.86 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5ZM.3 8h4.4v14H.3V8Zm7.2 0h4.22v1.92h.06c.59-1.12 2.03-2.3 4.18-2.3 4.47 0 5.3 2.94 5.3 6.77V22h-4.4v-6.75c0-1.61-.03-3.68-2.24-3.68-2.24 0-2.58 1.75-2.58 3.56V22H7.5V8Z"/>
                </svg>
                <span>LinkedIn</span>
            </a>

            <a href="https://twitter.com/intent/tweet?url=<?php echo esc_attr($url); ?>&text=<?php echo esc_attr($title); ?>"
               target="_blank"
               rel="noopener"
               aria-label="Partager sur X">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18.9 2h3.68l-8.04 9.19L24 22h-7.4l-5.8-7.57L4.17 22H.49l8.6-9.83L0 2h7.59l5.24 6.93L18.9 2Zm-1.29 18.1h2.04L6.48 3.8H4.29L17.61 20.1Z"/>
                </svg>
                <span>X</span>
            </a>

            <a href="mailto:?subject=<?php echo esc_attr($title); ?>&body=<?php echo esc_attr($url); ?>"
               aria-label="Partager par email">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 4H4C2.9 4 2 4.9 2 6v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4.25-8 5-8-5V6l8 5 8-5v2.25Z"/>
                </svg>
                <span>Email</span>
            </a>
        </nav>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('gdd_share_article', 'gdd_share_article_shortcode');


/**
 * Récupère le nom du chef d’un article.
 *
 * Priorité :
 * 1. Champ ACF nom_chef
 * 2. Partie du titre située avant le premier tiret
 */
function gdd_grid_get_chef_name($post_id)
{
    $chef = '';

    if (function_exists('get_field')) {
        $chef = get_field('nom_chef', $post_id);
    }

    if (!$chef) {
        $chef = get_post_meta(
            $post_id,
            'nom_chef',
            true
        );
    }

    $chef = trim(
        preg_replace(
            '/\s+/u',
            ' ',
            wp_strip_all_tags((string) $chef)
        )
    );

    if ($chef !== '') {
        return $chef;
    }

    $title = get_the_title($post_id);

    $parts = preg_split(
        '/\s+[-–—]\s+/u',
        $title,
        2
    );

    if (
        is_array($parts)
        && count($parts) > 1
        && trim($parts[0]) !== ''
    ) {
        return trim($parts[0]);
    }

    /*
     * Si aucun chef n’est identifiable, chaque article reçoit
     * une clé indépendante pour ne pas être regroupé.
     */
    return 'article-' . $post_id;
}


/**
 * Alterne les articles par chef.
 *
 * Exemple :
 * Chef A 1
 * Chef B 1
 * Chef C 1
 * Chef A 2
 * Chef B 2
 * Chef C 2
 */
function gdd_grid_balance_posts_by_chef($post_ids)
{
    $groups = [];

    foreach ($post_ids as $post_id) {
        $chef = gdd_grid_get_chef_name($post_id);

        /*
         * La clé permet de regrouper également :
         * David Galienne
         * DAVID GALIENNE
         * David  Galienne
         */
        $chef_key = sanitize_title($chef);

        if (!isset($groups[$chef_key])) {
            $groups[$chef_key] = [];
        }

        $groups[$chef_key][] = (int) $post_id;
    }

    /*
     * Les chefs ayant le plus de recettes sont placés en premier,
     * puis l’alternance évite leurs longues séries.
     */
    uasort(
        $groups,
        function ($group_a, $group_b) {
            return count($group_b) <=> count($group_a);
        }
    );

    $balanced_ids = [];

    do {
        $article_added = false;

        foreach ($groups as $chef_key => &$chef_posts) {
            if (!empty($chef_posts)) {
                $balanced_ids[] = array_shift($chef_posts);
                $article_added = true;
            }
        }

        unset($chef_posts);

    } while ($article_added);

    return $balanced_ids;
}

/**
 * ============================================================
 * GENS DE GOÛT — GRILLE DYNAMIQUE D’ARTICLES
 * ============================================================
 *
 * Exemples :
 *
 * Catégorie courante, 9 articles :
 * [gdd_blog_grid]
 *
 * Catégorie ID 6, 6 articles aléatoires :
 * [gdd_blog_grid category="6" posts="6" random="true"]
 *
 * Catégorie par slug, 12 articles récents :
 * [gdd_blog_grid category="recettes-faciles-de-chefs" posts="12"]
 *
 * Catégorie courante, ordre aléatoire :
 * [gdd_blog_grid category="current" posts="6" random="true"]
 */


function gdd_blog_grid_shortcode($atts)
{
    $atts = shortcode_atts(
        [
            /**
             * Nombre d’articles affichés.
             */
            'posts' => 9,

            /**
             * Catégorie :
             * - vide ou current : catégorie de l’URL ;
             * - nombre : ID de catégorie ;
             * - texte : slug de catégorie.
             */
            'category' => 'current',

            /**
             * true  : ordre aléatoire ;
             * false : articles les plus récents.
             */
            'random' => 'false',
             /**
             ** position de l'image dans la grille
             */
            'image_position' => 'center',
            'balance_chefs' => 'false',

            /**
             * Utilisé uniquement lorsque random="false".
             */
            'orderby' => 'date',
            'order'   => 'DESC',
        ],
        $atts,
        'gdd_blog_grid'
    );


    /*
     * ========================================================
     * 1. PARAMÈTRES
     * ========================================================
     */

    $posts_number = max(
        1,
        absint($atts['posts'])
    );

    $random = filter_var(
        $atts['random'],
        FILTER_VALIDATE_BOOLEAN
    );

    $balance_chefs = filter_var(
    $atts['balance_chefs'],
    FILTER_VALIDATE_BOOLEAN
);

    $category_value = trim(
        (string) $atts['category']
    );

    $category_term = null;


    /*
     * ========================================================
     * 2. IDENTIFICATION DE LA CATÉGORIE
     * ========================================================
     */

    /**
     * Catégorie automatiquement récupérée depuis l’URL.
     */
    if (
        $category_value === ''
        || strtolower($category_value) === 'current'
    ) {
        if (is_category()) {
            $queried_object = get_queried_object();

            if (
                $queried_object instanceof WP_Term
                && $queried_object->taxonomy === 'category'
            ) {
                $category_term = $queried_object;
            }
        }
    }

    /**
     * Catégorie fournie avec son identifiant numérique.
     */
    elseif (ctype_digit($category_value)) {
        $category_term = get_category(
            absint($category_value)
        );
    }

    /**
     * Catégorie fournie avec son slug.
     */
    else {
        $category_term = get_category_by_slug(
            sanitize_title($category_value)
        );
    }


    /*
     * La catégorie est obligatoire.
     */
    if (
        !$category_term
        || is_wp_error($category_term)
        || empty($category_term->term_id)
    ) {
        return '<p class="gdd-blog-message">
            La catégorie demandée est introuvable.
        </p>';
    }

    $category_id = (int) $category_term->term_id;


    /*
     * ========================================================
     * 3. PAGINATION
     * ========================================================
     *
     * La pagination est utilisée uniquement avec un ordre normal.
     *
     * Avec un ordre aléatoire, les résultats changent à chaque
     * chargement : une pagination produirait donc des doublons
     * ou des articles manquants.
     */

    $paged = 1;

    if (!$random) {
        $paged = max(
            1,
            absint(get_query_var('paged')),
            absint(get_query_var('page'))
        );
    }


    /*
     * ========================================================
     * 4. REQUÊTE WORDPRESS
     * ========================================================
     */

   /*
 * ========================================================
 * REQUÊTE WORDPRESS
 * ========================================================
 */

if ($balance_chefs) {

    /*
     * Première requête :
     * récupération de tous les identifiants de la catégorie,
     * classés du plus récent au plus ancien.
     */
    $all_post_ids = get_posts([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => -1,
        'fields'              => 'ids',
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,

        'tax_query' => [
            [
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => [$category_id],
                'include_children' => true,
                'operator'         => 'IN',
            ],
        ],
    ]);

    /*
     * Réorganisation des articles pour alterner les chefs.
     */
    $balanced_post_ids = gdd_grid_balance_posts_by_chef(
        $all_post_ids
    );

    $total_articles = count($balanced_post_ids);
    $total_pages    = (int) ceil(
        $total_articles / $posts_number
    );

    $offset = ($paged - 1) * $posts_number;

    $page_post_ids = array_slice(
        $balanced_post_ids,
        $offset,
        $posts_number
    );

    /*
     * Deuxième requête :
     * récupération uniquement des articles de la page actuelle,
     * en conservant l’ordre équilibré.
     */
    $articles = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts_number,
        'post__in'            => !empty($page_post_ids)
            ? $page_post_ids
            : [0],
        'orderby'             => 'post__in',
        'ignore_sticky_posts' => true,
    ]);

    /*
     * WP_Query ne connaît pas la pagination calculée manuellement.
     */
    $articles->found_posts   = $total_articles;
    $articles->max_num_pages = $total_pages;
}

else {

    $query_args = [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts_number,
        'ignore_sticky_posts' => true,

        'tax_query' => [
            [
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => [$category_id],
                'include_children' => true,
                'operator'         => 'IN',
            ],
        ],
    ];

    if ($random) {
        $query_args['orderby']       = 'rand';
        $query_args['no_found_rows'] = true;
    } else {
        $query_args['paged'] = $paged;

        $query_args['orderby'] = sanitize_key(
            $atts['orderby']
        );

        $query_args['order'] =
            strtoupper($atts['order']) === 'ASC'
                ? 'ASC'
                : 'DESC';
    }

    $articles = new WP_Query($query_args);
}


    /*
     * ========================================================
     * 5. AUCUN ARTICLE
     * ========================================================
     */

    if (!$articles->have_posts()) {
        return '<p class="gdd-blog-message">
            Aucun article disponible dans cette catégorie.
        </p>';
    }


    /*
     * ========================================================
     * 6. AFFICHAGE HTML
     * ========================================================
     */

   

ob_start();

/*
 * Identifiant unique pour cette instance du shortcode.
 */
$uid = wp_unique_id('gdd-blog-grid-');

/*
 * Nettoyage de la valeur object-position.
 */
$image_position = sanitize_text_field(
    $atts['image_position']
);

/*
 * Valeur de secours si le paramètre est vide.
 */
if ($image_position === '') {
    $image_position = 'center';
}
?>

<style>
    #<?php echo esc_attr($uid); ?> .gdd-blog-image {
        object-position: <?php echo esc_attr($image_position); ?>;
    }
</style>

<div
    id="<?php echo esc_attr($uid); ?>"
    class="gdd-blog-grid"
>

    	

        <?php while ($articles->have_posts()) : ?>

            <?php
            $articles->the_post();

            $post_id   = get_the_ID();
            $permalink = get_permalink($post_id);
            $title     = get_the_title($post_id);

            $post_categories = get_the_category(
                $post_id
            );

            $display_category = null;


            /*
             * Sur une catégorie parente, on privilégie
             * une sous-catégorie attribuée à l’article.
             */
            foreach ($post_categories as $post_category) {
                if (
                    (int) $post_category->term_id
                    ===
                    $category_id
                ) {
                    continue;
                }

                if (
                    term_is_ancestor_of(
                        $category_id,
                        $post_category->term_id,
                        'category'
                    )
                ) {
                    $display_category = $post_category;
                    break;
                }
            }


            /*
             * À défaut, affiche la catégorie sélectionnée.
             */
            if (!$display_category) {
                $display_category = $category_term;
            }
            ?>

            <article class="gdd-blog-card">

                <a
                    class="gdd-blog-image-link"
                    href="<?php echo esc_url($permalink); ?>"
                    aria-label="<?php
                    echo esc_attr(
                        'Lire : ' . $title
                    );
                    ?>"
                >

                    <?php if (has_post_thumbnail($post_id)) : ?>

                        <?php
                        echo get_the_post_thumbnail(
                            $post_id,
                            'large',
                            [
                                'class'   => 'gdd-blog-image',
                                'loading' => 'lazy',
                            ]
                        );
                        ?>

                    <?php else : ?>

                        <span class="gdd-blog-image-placeholder">
                            Image à venir
                        </span>

                    <?php endif; ?>

                </a>


                <div class="gdd-blog-content">

                    <?php if ($display_category) : ?>

                        <a
                            class="gdd-blog-category"
                            href="<?php
                            echo esc_url(
                                get_category_link(
                                    $display_category->term_id
                                )
                            );
                            ?>"
                        >
                            <?php
                            echo esc_html(
                                $display_category->name
                            );
                            ?>
                        </a>

                    <?php endif; ?>


                    <h2 class="gdd-blog-title">

                        <a href="<?php
                        echo esc_url($permalink);
                        ?>">
                            <?php
                            echo esc_html($title);
                            ?>
                        </a>

                    </h2>

                </div>

            </article>

        <?php endwhile; ?>

    </div>


    <?php
    /*
     * ========================================================
     * 7. PAGINATION
     * ========================================================
     *
     * Elle est volontairement désactivée en mode aléatoire.
     */

   if (
    (!$random || $balance_chefs)
    && (int) $articles->max_num_pages > 1
) {
        $pagination = paginate_links(
            [
                'total'     => (int) $articles->max_num_pages,
                'current'   => $paged,
                'mid_size'  => 2,
                'end_size'  => 1,
                'prev_text' => '‹',
                'next_text' => '›',
                'type'      => 'list',
            ]
        );

        if ($pagination) {
            ?>

            <nav
                class="gdd-pagination"
                aria-label="Pagination des articles"
            >
                <?php
                echo wp_kses_post($pagination);
                ?>
            </nav>

            <?php
        }
    }


    wp_reset_postdata();

    return ob_get_clean();
}


add_shortcode(
    'gdd_blog_grid',
    'gdd_blog_grid_shortcode'
);


/**
 * Recherche compacte destinée à l'en-tête Divi.
 *
 * Exemple :
 * [gdd_recipe_search_header target="/category/recettes-faciles-de-chefs/"]
 */
function gdd_recipe_search_header_shortcode($atts) {

    $atts = shortcode_atts(
        [
            /*
             * URL de la page ou de la catégorie qui contient
             * le shortcode principal [gdd_recipe_search].
             */
            'target' => '/category/recettes-de-chef/',
        ],
        $atts,
        'gdd_recipe_search_header'
    );

    $target_url = home_url($atts['target']);

    /*
     * Réutilise la fonction créée précédemment pour obtenir
     * automatiquement les chefs publiés.
     */
    $chefs = function_exists('gdd_get_recipe_chefs')
        ? gdd_get_recipe_chefs(0)
        : [];

    $unique_id = wp_unique_id('gdd-header-search-');

    ob_start();
    ?>

    <details
        class="gdd-header-search"
        id="<?php echo esc_attr($unique_id); ?>"
    >
        <summary
            class="gdd-header-search-toggle"
            aria-label="Ouvrir la recherche de recettes"
        >
            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
                focusable="false"
            >
                <path
                    d="M10.7 4a6.7 6.7 0 1 0 0 13.4A6.7 6.7 0 0 0 10.7 4Zm0 1.8a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8Zm5.85 9.48 3.45 3.45-1.27 1.27-3.45-3.45 1.27-1.27Z"
                />
            </svg>

            <span class="gdd-header-search-text">
                Rechercher
            </span>
        </summary>

        <div class="gdd-header-search-panel">

            <form
                class="gdd-header-search-form"
                method="get"
                action="<?php echo esc_url($target_url); ?>"
            >
                <div class="gdd-header-search-field">

                    <label
                        for="<?php echo esc_attr($unique_id); ?>-chef"
                    >
                        Chef
                    </label>

                    <input
                        type="search"
                        id="<?php echo esc_attr($unique_id); ?>-chef"
                        name="gdd_chef"
                        list="<?php echo esc_attr($unique_id); ?>-chefs"
                        placeholder="Nom du chef"
                        autocomplete="off"
                    >

                    <datalist
                        id="<?php echo esc_attr($unique_id); ?>-chefs"
                    >
                        <?php foreach ($chefs as $chef) : ?>

                            <option
                                value="<?php echo esc_attr($chef); ?>"
                            ></option>

                        <?php endforeach; ?>
                    </datalist>

                </div>

                <div class="gdd-header-search-field">

                    <label
                        for="<?php echo esc_attr($unique_id); ?>-ingredient"
                    >
                        Recette ou ingrédient
                    </label>

                    <input
                        type="search"
                        id="<?php echo esc_attr($unique_id); ?>-ingredient"
                        name="gdd_ingredient"
                        placeholder="Truffe, homard, chocolat…"
                        autocomplete="off"
                    >

                </div>

                <button
                    type="submit"
                    class="gdd-header-search-submit"
                    aria-label="Lancer la recherche"
                >
                    <span>Rechercher</span>

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        focusable="false"
                    >
                        <path
                            d="M10.7 4a6.7 6.7 0 1 0 0 13.4A6.7 6.7 0 0 0 10.7 4Zm0 1.8a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8Zm5.85 9.48 3.45 3.45-1.27 1.27-3.45-3.45 1.27-1.27Z"
                        />
                    </svg>
                </button>

            </form>

        </div>
    </details>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'gdd_recipe_search_header',
    'gdd_recipe_search_header_shortcode'
);

/**
 * Moteur de recherche des recettes Gens de Goût.
 *
 * Shortcode :
 * [gdd_recipe_search]
 *
 * Options :
 * [gdd_recipe_search posts="12"]
 */

function gdd_get_current_recipe_category_id() {

    if (is_category()) {
        $term = get_queried_object();

        if (
            $term instanceof WP_Term
            && $term->taxonomy === 'category'
        ) {
            return (int) $term->term_id;
        }
    }

    return 0;
}


/**
 * Récupère le nom du chef.
 *
 * Priorité :
 * 1. Champ personnalisé nom_chef
 * 2. Partie du titre située avant le premier tiret
 */
function gdd_get_post_chef_name($post_id) {

    $acf_chef = get_post_meta(
        $post_id,
        'nom_chef',
        true
    );

    if (!empty($acf_chef)) {
        return trim(wp_strip_all_tags($acf_chef));
    }

    $title = get_the_title($post_id);

    /*
     * Compatible avec :
     * Paul Bocuse - Soupe VGE
     * Paul Bocuse – Soupe VGE
     * Paul Bocuse — Soupe VGE
     */
    $parts = preg_split(
        '/\s+[-–—]\s+/',
        $title,
        2
    );

    if (!empty($parts[0]) && count($parts) > 1) {
        return trim($parts[0]);
    }

    return '';
}


/**
 * Génère la liste des chefs pour l’autocomplétion.
 */
function gdd_get_recipe_chefs($category_id = 0) {

    $query_args = [
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => 500,
        'fields'                 => 'ids',
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ];

    if ($category_id) {
        $query_args['tax_query'] = [
            [
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => [$category_id],
                'include_children' => true,
            ],
        ];
    }

    $post_ids = get_posts($query_args);
    $chefs    = [];

    foreach ($post_ids as $post_id) {
        $chef = gdd_get_post_chef_name($post_id);

        if ($chef !== '') {
            $chefs[$chef] = $chef;
        }
    }

    natcasesort($chefs);

    return array_values($chefs);
}


/**
 * Shortcode principal.
 */
function gdd_recipe_search_shortcode($atts) {

    $atts = shortcode_atts(
        [
            'posts' => 12,
        ],
        $atts,
        'gdd_recipe_search'
    );

    $posts_per_page = max(
        1,
        absint($atts['posts'])
    );

    $chef = isset($_GET['gdd_chef'])
        ? sanitize_text_field(
            wp_unslash($_GET['gdd_chef'])
        )
        : '';

    $ingredient = isset($_GET['gdd_ingredient'])
        ? sanitize_text_field(
            wp_unslash($_GET['gdd_ingredient'])
        )
        : '';

    $current_page = isset($_GET['gdd_page'])
        ? max(1, absint($_GET['gdd_page']))
        : 1;

    $category_id = gdd_get_current_recipe_category_id();
    $chefs       = gdd_get_recipe_chefs($category_id);

    $query_args = [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts_per_page,
        'paged'               => $current_page,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
    ];

    if ($category_id) {
        $query_args['tax_query'] = [
            [
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => [$category_id],
                'include_children' => true,
            ],
        ];
    }

    /*
     * Filtre SQL personnalisé.
     *
     * Chef :
     * - recherche dans le champ personnalisé nom_chef ;
     * - ou dans le titre de l’article.
     *
     * Ingrédient :
     * - recherche uniquement dans le titre.
     */
    $where_filter = function ($where, $query) use ($chef, $ingredient) {

        global $wpdb;

        if (!$query->get('gdd_recipe_search')) {
            return $where;
        }

        if ($chef !== '') {
            $chef_like = '%' . $wpdb->esc_like($chef) . '%';

            $where .= $wpdb->prepare(
                "
                AND (
                    {$wpdb->posts}.post_title LIKE %s
                    OR EXISTS (
                        SELECT 1
                        FROM {$wpdb->postmeta} AS gdd_chef_meta
                        WHERE gdd_chef_meta.post_id = {$wpdb->posts}.ID
                        AND gdd_chef_meta.meta_key = 'nom_chef'
                        AND gdd_chef_meta.meta_value LIKE %s
                    )
                )
                ",
                $chef_like,
                $chef_like
            );
        }

        if ($ingredient !== '') {
            $ingredient_like =
                '%' . $wpdb->esc_like($ingredient) . '%';

            $where .= $wpdb->prepare(
                " AND {$wpdb->posts}.post_title LIKE %s ",
                $ingredient_like
            );
        }

        return $where;
    };

    $query_args['gdd_recipe_search'] = true;

    add_filter(
        'posts_where',
        $where_filter,
        10,
        2
    );

    $recipes = new WP_Query($query_args);

    remove_filter(
        'posts_where',
        $where_filter,
        10
    );

    ob_start();
    ?>

    <section class="gdd-recipe-search">

        <form
            class="gdd-recipe-search-form"
            method="get"
            action=""
        >

            <div class="gdd-search-field">

                <label for="gdd-chef">
                    Rechercher par chef
                </label>

                <input
                    type="search"
                    id="gdd-chef"
                    name="gdd_chef"
                    value="<?php echo esc_attr($chef); ?>"
                    list="gdd-chefs-list"
                    placeholder="Ex. Paul Bocuse"
                    autocomplete="off"
                >

                <datalist id="gdd-chefs-list">

                    <?php foreach ($chefs as $chef_name) : ?>

                        <option
                            value="<?php
                            echo esc_attr($chef_name);
                            ?>"
                        ></option>

                    <?php endforeach; ?>

                </datalist>

            </div>

            <div class="gdd-search-field">

                <label for="gdd-ingredient">
                    Rechercher par ingrédient
                </label>

                <input
                    type="search"
                    id="gdd-ingredient"
                    name="gdd_ingredient"
                    value="<?php
                    echo esc_attr($ingredient);
                    ?>"
                    placeholder="Ex. truffe, homard, asperge…"
                    autocomplete="off"
                >

            </div>

            <div class="gdd-search-actions">

                <button
                    type="submit"
                    class="gdd-search-submit"
                >
                    Rechercher
                </button>

                <?php if ($chef !== '' || $ingredient !== '') : ?>

                    <a
                        class="gdd-search-reset"
                        href="<?php
                        echo esc_url(
                            remove_query_arg(
                                [
                                    'gdd_chef',
                                    'gdd_ingredient',
                                    'gdd_page',
                                ]
                            )
                        );
                        ?>"
                    >
                        Effacer
                    </a>

                <?php endif; ?>

            </div>

        </form>

        <?php if ($chef !== '' || $ingredient !== '') : ?>

            <div class="gdd-search-summary">

                <strong>
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            $recipes->found_posts
                        )
                    );
                    ?>
                </strong>

                <?php
                echo $recipes->found_posts > 1
                    ? ' recettes trouvées'
                    : ' recette trouvée';
                ?>

                <?php if ($chef !== '') : ?>

                    pour le chef

                    <strong>
                        <?php echo esc_html($chef); ?>
                    </strong>

                <?php endif; ?>

                <?php if ($ingredient !== '') : ?>

                    avec le terme

                    <strong>
                        « <?php
                        echo esc_html($ingredient);
                        ?> »
                    </strong>

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <?php if ($recipes->have_posts()) : ?>

            <div class="gdd-search-results">

                <?php
                while ($recipes->have_posts()) :
                    $recipes->the_post();

                    $post_id    = get_the_ID();
                    $permalink  = get_permalink();
                    $title      = get_the_title();
                    $categories = get_the_category();
                    $chef_name  = gdd_get_post_chef_name(
                        $post_id
                    );

                    $display_category = !empty($categories)
                        ? $categories[0]
                        : null;
                    ?>

                    <article class="gdd-search-card">

                        <a
                            class="gdd-search-card-image"
                            href="<?php
                            echo esc_url($permalink);
                            ?>"
                            aria-label="<?php
                            echo esc_attr(
                                'Lire la recette : ' . $title
                            );
                            ?>"
                        >

                            <?php if (has_post_thumbnail()) : ?>

                                <?php
                                the_post_thumbnail(
                                    'large',
                                    [
                                        'class'   =>
                                            'gdd-search-image',
                                        'loading' =>
                                            'lazy',
                                    ]
                                );
                                ?>

                            <?php else : ?>

                                <span
                                    class="gdd-search-placeholder"
                                >
                                    Image à venir
                                </span>

                            <?php endif; ?>

                        </a>

                        <div class="gdd-search-card-content">

                            <div class="gdd-search-card-meta">

                                <?php if ($chef_name !== '') : ?>

                                    <span class="gdd-search-chef">
                                        <?php
                                        echo esc_html(
                                            $chef_name
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>

                                <?php if ($display_category) : ?>

                                    <a
                                        class="gdd-search-category"
                                        href="<?php
                                        echo esc_url(
                                            get_category_link(
                                                $display_category
                                                    ->term_id
                                            )
                                        );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $display_category
                                                ->name
                                        );
                                        ?>
                                    </a>

                                <?php endif; ?>

                            </div>

                            <h2 class="gdd-search-title">

                                <a href="<?php
                                echo esc_url($permalink);
                                ?>">
                                    <?php
                                    echo esc_html($title);
                                    ?>
                                </a>

                            </h2>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

            <?php
            $total_pages = (int) $recipes->max_num_pages;

            if ($total_pages > 1) :

                $base_url = remove_query_arg(
                    'gdd_page'
                );

                $pagination = paginate_links(
                    [
                        'base'      => add_query_arg(
                            'gdd_page',
                            '%#%',
                            $base_url
                        ),
                        'format'    => '',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                        'mid_size'  => 2,
                        'end_size'  => 1,
                        'prev_text' => '‹',
                        'next_text' => '›',
                        'type'      => 'list',
                    ]
                );

                if ($pagination) :
                    ?>

                    <nav
                        class="gdd-search-pagination"
                        aria-label="Pagination des recettes"
                    >
                        <?php echo wp_kses_post($pagination); ?>
                    </nav>

                <?php endif; ?>

            <?php endif; ?>

        <?php else : ?>

            <div class="gdd-search-empty">

                <p>
                    Aucune recette ne correspond à votre recherche.
                </p>

                <a href="<?php
                echo esc_url(
                    remove_query_arg(
                        [
                            'gdd_chef',
                            'gdd_ingredient',
                            'gdd_page',
                        ]
                    )
                );
                ?>">
                    Afficher toutes les recettes
                </a>

            </div>

        <?php endif; ?>

    </section>

    <?php

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode(
    'gdd_recipe_search',
    'gdd_recipe_search_shortcode'
);
/**
 * ============================================================
 * GENS DE GOÛT — COLONNES ACF DANS LE BACK-OFFICE WORDPRESS
 * ============================================================
 *
 * Fonctionnalités :
 * - Photo du chef
 * - Nom du chef
 * - Établissement
 * - Ville
 * - Distinction
 * - Tri par nom du chef
 * - Filtre déroulant par chef
 * - Limitation possible aux articles de recettes
 */


/**
 * ============================================================
 * 1. CONFIGURATION
 * ============================================================
 */

/**
 * Slug de la catégorie parente des recettes.
 *
 * Vérifie le slug exact dans :
 * Articles > Catégories
 */
define(
    'GDD_RECIPES_CATEGORY_SLUG',
    'recettes-de-chef'
);


/**
 * Noms techniques des champs ACF.
 *
 * Modifie uniquement les valeurs de droite si tes noms ACF
 * sont différents.
 */
define('GDD_ACF_CHEF', 'nom_chef');
define('GDD_ACF_PHOTO_CHEF', 'photo_chef');
define('GDD_ACF_RESTAURANT', 'resto_chef');
define('GDD_ACF_VILLE', 'ville_chef');
define('GDD_ACF_DISTINCTION', 'distinctions_chef');


/**
 * Si true :
 * les valeurs sont affichées uniquement pour les articles
 * appartenant à la catégorie des recettes ou à ses enfants.
 *
 * Si false :
 * les colonnes sont utilisées pour tous les articles.
 */
define('GDD_LIMIT_ADMIN_COLUMNS_TO_RECIPES', false);


/**
 * ============================================================
 * 2. RÉCUPÉRER UNE VALEUR ACF
 * ============================================================
 */

/**
 * Utilise get_field() si ACF est actif.
 * Sinon, récupère directement la valeur avec get_post_meta().
 */
function gdd_get_acf_value($field_name, $post_id)
{
    if (
        function_exists('get_field')
        && is_string($field_name)
        && $field_name !== ''
    ) {
        return get_field($field_name, $post_id);
    }

    return get_post_meta(
        $post_id,
        $field_name,
        true
    );
}


/**
 * ============================================================
 * 3. VÉRIFIER SI UN ARTICLE EST UNE RECETTE
 * ============================================================
 */

/**
 * Retourne true lorsque l’article appartient :
 * - à la catégorie parente des recettes ;
 * - ou à l’une de ses sous-catégories.
 */
function gdd_is_recipe_post($post_id)
{
    $parent_category = get_category_by_slug(
        GDD_RECIPES_CATEGORY_SLUG
    );

    if (!$parent_category) {
        return false;
    }

    $post_categories = wp_get_post_categories(
        $post_id,
        [
            'fields' => 'all',
        ]
    );

    if (empty($post_categories)) {
        return false;
    }

    foreach ($post_categories as $category) {

        /**
         * L’article appartient directement
         * à la catégorie parente.
         */
        if (
            (int) $category->term_id
            ===
            (int) $parent_category->term_id
        ) {
            return true;
        }

        /**
         * L’article appartient à une sous-catégorie
         * directe ou indirecte de la catégorie parente.
         */
        if (
            term_is_ancestor_of(
                $parent_category->term_id,
                $category->term_id,
                'category'
            )
        ) {
            return true;
        }
    }

    return false;
}


/**
 * Détermine si les champs doivent être affichés
 * pour l’article concerné.
 */
function gdd_should_display_admin_values($post_id)
{
    if (!GDD_LIMIT_ADMIN_COLUMNS_TO_RECIPES) {
        return true;
    }

    return gdd_is_recipe_post($post_id);
}


/**
 * ============================================================
 * 4. AJOUTER LES COLONNES DANS ARTICLES > TOUS LES ARTICLES
 * ============================================================
 */

function gdd_add_admin_post_columns($columns)
{
    $new_columns = [];

    foreach ($columns as $column_key => $column_label) {

        $new_columns[$column_key] = $column_label;

        /**
         * Insère les nouvelles colonnes juste après le titre.
         */
        if ($column_key === 'title') {

            $new_columns['gdd_photo_chef'] = 'Photo';
            $new_columns['gdd_chef'] = 'Chef';
            $new_columns['gdd_restaurant'] = 'Établissement';
            $new_columns['gdd_ville'] = 'Ville';
            $new_columns['gdd_distinction'] = 'Distinction';
        }
    }

    return $new_columns;
}

add_filter(
    'manage_post_posts_columns',
    'gdd_add_admin_post_columns'
);


/**
 * ============================================================
 * 5. AFFICHER LES VALEURS DANS LES COLONNES
 * ============================================================
 */

function gdd_render_admin_post_columns($column_name, $post_id)
{
    /**
     * Si la limitation aux recettes est activée,
     * les autres articles affichent un tiret.
     */
    if (!gdd_should_display_admin_values($post_id)) {

        if (
            in_array(
                $column_name,
                [
                    'gdd_photo_chef',
                    'gdd_chef',
                    'gdd_restaurant',
                    'gdd_ville',
                    'gdd_distinction',
                ],
                true
            )
        ) {
            echo '<span class="gdd-admin-empty">—</span>';
        }

        return;
    }

    switch ($column_name) {

        /**
         * Photo du chef.
         */
        case 'gdd_photo_chef':

            $photo = gdd_get_acf_value(
                GDD_ACF_PHOTO_CHEF,
                $post_id
            );

            if (!$photo) {
                echo '<span class="gdd-admin-empty">—</span>';
                break;
            }

            $image_id  = 0;
            $image_url = '';

            /**
             * ACF retourne un tableau.
             */
            if (is_array($photo)) {

                $image_id = isset($photo['ID'])
                    ? absint($photo['ID'])
                    : 0;

                $image_url = isset($photo['url'])
                    ? esc_url($photo['url'])
                    : '';
            }

            /**
             * ACF retourne un ID média.
             */
            elseif (is_numeric($photo)) {

                $image_id = absint($photo);
            }

            /**
             * ACF retourne une URL.
             */
            elseif (is_string($photo)) {

                $image_url = esc_url($photo);
            }

            if ($image_id) {

                echo wp_get_attachment_image(
                    $image_id,
                    [56, 56],
                    false,
                    [
                        'class'   => 'gdd-admin-chef-photo',
                        'loading' => 'lazy',
                        'alt'     => '',
                    ]
                );
            }

            elseif ($image_url) {

                printf(
                    '<img
                        class="gdd-admin-chef-photo"
                        src="%s"
                        alt=""
                        loading="lazy"
                    >',
                    esc_url($image_url)
                );
            }

            else {

                echo '<span class="gdd-admin-empty">—</span>';
            }

            break;


        /**
         * Nom du chef.
         */
        case 'gdd_chef':

            $chef = gdd_get_acf_value(
                GDD_ACF_CHEF,
                $post_id
            );

            if ($chef) {

                printf(
                    '<strong class="gdd-admin-chef-name">%s</strong>',
                    esc_html($chef)
                );
            }

            else {

                echo '<span class="gdd-admin-empty">—</span>';
            }

            break;


        /**
         * Établissement.
         */
        case 'gdd_restaurant':

            $restaurant = gdd_get_acf_value(
                GDD_ACF_RESTAURANT,
                $post_id
            );

            echo $restaurant
                ? esc_html($restaurant)
                : '<span class="gdd-admin-empty">—</span>';

            break;


        /**
         * Ville.
         */
        case 'gdd_ville':

            $ville = gdd_get_acf_value(
                GDD_ACF_VILLE,
                $post_id
            );

            echo $ville
                ? esc_html($ville)
                : '<span class="gdd-admin-empty">—</span>';

            break;


        /**
         * Distinction.
         */
        case 'gdd_distinction':

            $distinction = gdd_get_acf_value(
                GDD_ACF_DISTINCTION,
                $post_id
            );

            echo $distinction
                ? esc_html($distinction)
                : '<span class="gdd-admin-empty">—</span>';

            break;
    }
}

add_action(
    'manage_post_posts_custom_column',
    'gdd_render_admin_post_columns',
    10,
    2
);


/**
 * ============================================================
 * 6. RENDRE LA COLONNE CHEF TRIABLE
 * ============================================================
 */

function gdd_make_admin_columns_sortable($columns)
{
    $columns['gdd_chef'] = 'gdd_chef';

    return $columns;
}

add_filter(
    'manage_edit-post_sortable_columns',
    'gdd_make_admin_columns_sortable'
);


/**
 * Applique le tri alphabétique sur le champ ACF nom_chef.
 */
function gdd_apply_admin_chef_sorting($query)
{
    if (
        !is_admin()
        || !$query->is_main_query()
        || $query->get('post_type') !== 'post'
        || $query->get('orderby') !== 'gdd_chef'
    ) {
        return;
    }

    $query->set(
        'meta_key',
        GDD_ACF_CHEF
    );

    $query->set(
        'orderby',
        'meta_value'
    );
}

add_action(
    'pre_get_posts',
    'gdd_apply_admin_chef_sorting'
);


/**
 * ============================================================
 * 7. AJOUTER UN FILTRE DÉROULANT PAR CHEF
 * ============================================================
 */

function gdd_add_admin_chef_filter($post_type)
{
    if ($post_type !== 'post') {
        return;
    }

    global $wpdb;

    $selected_chef = isset($_GET['gdd_admin_chef'])
        ? sanitize_text_field(
            wp_unslash($_GET['gdd_admin_chef'])
        )
        : '';

    /**
     * Récupère les valeurs uniques du champ nom_chef.
     */
    $chef_field = GDD_ACF_CHEF;

    $chefs = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT DISTINCT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = %s
            AND meta_value <> ''
            ORDER BY meta_value ASC
            ",
            $chef_field
        )
    );

    if (empty($chefs)) {
        return;
    }

    echo '<select name="gdd_admin_chef">';

    echo '<option value="">Tous les chefs</option>';

    foreach ($chefs as $chef) {

        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($chef),
            selected(
                $selected_chef,
                $chef,
                false
            ),
            esc_html($chef)
        );
    }

    echo '</select>';
}

add_action(
    'restrict_manage_posts',
    'gdd_add_admin_chef_filter'
);


/**
 * Applique le filtre sélectionné à la requête des articles.
 */
function gdd_apply_admin_chef_filter($query)
{
    if (
        !is_admin()
        || !$query->is_main_query()
        || $query->get('post_type') !== 'post'
        || empty($_GET['gdd_admin_chef'])
    ) {
        return;
    }

    $selected_chef = sanitize_text_field(
        wp_unslash($_GET['gdd_admin_chef'])
    );

    /**
     * Préserve une éventuelle meta_query existante.
     */
    $meta_query = $query->get('meta_query');

    if (!is_array($meta_query)) {
        $meta_query = [];
    }

    $meta_query[] = [
        'key'     => GDD_ACF_CHEF,
        'value'   => $selected_chef,
        'compare' => '=',
    ];

    $query->set(
        'meta_query',
        $meta_query
    );
}

add_action(
    'pre_get_posts',
    'gdd_apply_admin_chef_filter'
);


/**
 * ============================================================
 * 8. STYLE DU BACK-OFFICE
 * ============================================================
 */

function gdd_admin_columns_styles()
{
    $screen = get_current_screen();

    if (
        !$screen
        || $screen->base !== 'edit'
        || $screen->post_type !== 'post'
    ) {
        return;
    }

    ?>
    <style>
        .column-gdd_photo_chef {
            width: 72px;
            text-align: center;
        }

        .column-gdd_chef {
            width: 150px;
        }

        .column-gdd_restaurant {
            width: 175px;
        }

        .column-gdd_ville {
            width: 115px;
        }

        .column-gdd_distinction {
            width: 145px;
        }

        .gdd-admin-chef-photo {
            display: block;
            width: 52px;
            height: 52px;
            margin: 0 auto;
            border-radius: 50%;
            object-fit: cover;
            object-position: center;
            background: #f0f0f1;
        }

        .gdd-admin-chef-name {
            color: #1d2327;
            font-weight: 600;
        }

        .gdd-admin-empty {
            color: #a7aaad;
        }

        @media screen and (max-width: 1200px) {
            .column-gdd_distinction {
                display: none;
            }
        }

        @media screen and (max-width: 1050px) {
            .column-gdd_ville {
                display: none;
            }
        }

        @media screen and (max-width: 900px) {
            .column-gdd_restaurant {
                display: none;
            }
        }
    </style>
    <?php
}

add_action(
    'admin_head-edit.php',
    'gdd_admin_columns_styles'
);
