<?php
/**
 * Quick Edit: ACF field retombee_publiee on posts only.
 * Requires an existing ACF true_false field.
 */
if (!defined('ABSPATH')) { exit; }

add_filter('manage_post_posts_columns', static function ($columns) {
    $columns['gdd_retombee_publiee'] = 'Retombée publiée';
    return $columns;
}, 50);

add_action('manage_post_posts_custom_column', static function ($column, $post_id) {
    if ($column !== 'gdd_retombee_publiee') { return; }
    $value = get_post_meta($post_id, 'retombee_publiee', true);
    $checked = ($value === '1' || $value === 1 || $value === true);
    echo '<span class="gdd-retombee-value" data-value="' . esc_attr($checked ? '1' : '0') . '">'
        . esc_html($checked ? 'Oui' : 'Non') . '</span>';
}, 10, 2);

add_action('quick_edit_custom_box', static function ($column, $post_type) {
    if ($column !== 'gdd_retombee_publiee' || $post_type !== 'post' || !current_user_can('edit_posts')) {
        return;
    }
    wp_nonce_field('gdd_retombee_quick_edit', 'gdd_retombee_quick_nonce');
    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
    echo '<label><span class="title">Retombée publiée</span>';
    echo '<select name="gdd_retombee_publiee_quick">';
    echo '<option value="">Ne pas modifier</option>';
    echo '<option value="1">Oui</option>';
    echo '<option value="0">Non</option>';
    echo '</select></label></div></fieldset>';
}, 10, 2);

add_action('admin_footer-edit.php', static function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'post' || !current_user_can('edit_posts')) { return; }
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
            if (state && select) { select.value = state.getAttribute('data-value') === '1' ? '1' : '0'; }
        };
    })();
    </script>
    <?php
});

add_action('save_post_post', static function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) { return; }
    if (!isset($_POST['gdd_retombee_quick_nonce']) || !is_string($_POST['gdd_retombee_quick_nonce'])) { return; }
    $nonce = sanitize_text_field(wp_unslash($_POST['gdd_retombee_quick_nonce']));
    if (!wp_verify_nonce($nonce, 'gdd_retombee_quick_edit')) { return; }
    if (!isset($_POST['gdd_retombee_publiee_quick']) || !is_string($_POST['gdd_retombee_publiee_quick'])) { return; }
    $value = sanitize_text_field(wp_unslash($_POST['gdd_retombee_publiee_quick']));
    if ($value !== '0' && $value !== '1') { return; }
    if (function_exists('update_field')) {
        update_field('retombee_publiee', (int) $value, $post_id);
    } else {
        update_post_meta($post_id, 'retombee_publiee', $value);
    }
}, 10, 1);
