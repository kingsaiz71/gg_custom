<?php
/**
 * Colonne et modification rapide du champ ACF radio retombee_publiee.
 * Choix réels : publiee / apubliee. Les articles sans valeur sont affichés Publiée.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_filter('manage_post_posts_columns', static function ($columns) {
    $result = [];
    $inserted = false;
    foreach ($columns as $key => $label) {
        if ($key === 'gdd_distinction') {
            $result['gdd_retombee_publiee'] = 'Retombées';
            $inserted = true;
            continue;
        }
        if ($key === 'gdd_retombee_publiee') {
            continue;
        }
        $result[$key] = $label;
    }
    if (!$inserted) {
        $result['gdd_retombee_publiee'] = 'Retombées';
    }
    return $result;
}, 100);

add_action('manage_post_posts_custom_column', static function ($column, $post_id) {
    if ($column !== 'gdd_retombee_publiee') {
        return;
    }
    $raw = get_post_meta($post_id, 'retombee_publiee', true);
    $value = $raw === 'publiee' ? 'publiee' : 'apubliee';
    $is_published = $value === 'publiee';
    $label = $is_published ? 'Publiée' : 'À publier';
    $color = $is_published ? '#16743d' : '#b42318';
    $symbol = $is_published ? '✔' : '✘';
    echo '<span class="gdd-retombee-value" data-value="' . esc_attr($value) . '" style="color:' . esc_attr($color) . ';font-weight:800;white-space:nowrap;">'
        . esc_html($symbol . ' ' . $label) . '</span>';
}, 10, 2);

add_action('quick_edit_custom_box', static function ($column, $post_type) {
    if ($column !== 'gdd_retombee_publiee' || $post_type !== 'post' || !current_user_can('edit_posts')) {
        return;
    }
    wp_nonce_field('gdd_retombee_quick_edit', 'gdd_retombee_quick_nonce');
    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
    echo '<label><span class="title">Retombées</span>';
    echo '<select name="gdd_retombee_publiee_quick">';
    echo '<option value="apubliee">À publier</option>';
    echo '<option value="apubliee">À publier</option>';
    echo '</select></label></div></fieldset>';
}, 10, 2);

add_action('admin_footer-edit.php', static function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'post' || !current_user_can('edit_posts')) {
        return;
    }
    ?>
    <script>
    (function() {
        if (typeof inlineEditPost === 'undefined' || !inlineEditPost.edit) { return; }
        var original = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            original.apply(this, arguments);
            var postId = (typeof id === 'object') ? this.getId(id) : id;
            if (!/^[0-9]+$/.test(String(postId))) { return; }
            var row = document.getElementById('post-' + postId);
            var editor = document.getElementById('edit-' + postId);
            if (!row || !editor) { return; }
            var state = row.querySelector('.gdd-retombee-value');
            var select = editor.querySelector('select[name="gdd_retombee_publiee_quick"]');
            if (select) {
                select.value = state && state.getAttribute('data-value') === 'publiee' ? 'publiee' : 'apubliee';
            }
        };
    })();
    </script>
    <?php
});

add_action('save_post_post', static function ($post_id) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['gdd_retombee_quick_nonce']) || !is_string($_POST['gdd_retombee_quick_nonce'])) {
        return;
    }
    $nonce = sanitize_text_field(wp_unslash($_POST['gdd_retombee_quick_nonce']));
    if (!wp_verify_nonce($nonce, 'gdd_retombee_quick_edit')) {
        return;
    }
    if (!isset($_POST['gdd_retombee_publiee_quick']) || !is_string($_POST['gdd_retombee_publiee_quick'])) {
        return;
    }
    $value = sanitize_text_field(wp_unslash($_POST['gdd_retombee_publiee_quick']));
    if (!in_array($value, ['publiee', 'apubliee'], true)) {
        return;
    }
    if (function_exists('update_field')) {
        update_field('retombee_publiee', $value, $post_id);
    } else {
        update_post_meta($post_id, 'retombee_publiee', $value);
    }
});
