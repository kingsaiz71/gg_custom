<?php
/**
 * GitHub Releases updater for the public GG Custom repository.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_filter('update_plugins_gensdegout.fr', function ($update, $plugin_data, $plugin_file, $locales) {
    if ($plugin_file !== 'gdd-fonctions-personnalisees/gdd-fonctions-personnalisees.php') {
        return $update;
    }

    $release = get_transient('gdd_github_release');
    if (false === $release) {
        $response = wp_remote_get('https://api.github.com/repos/kingsaiz71/gg_custom/releases/latest', [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'GensDeGoutPluginUpdater',
            ],
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return $update;
        }
        $release = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($release)) {
            return $update;
        }
        set_transient('gdd_github_release', $release, 6 * HOUR_IN_SECONDS);
    }

    $tag = $release['tag_name'] ?? '';
    if (!preg_match('/^v([0-9]+\.[0-9]+\.[0-9]+)$/', $tag, $matches)) {
        return $update;
    }
    $version = $matches[1];
    if (!version_compare($version, $plugin_data['Version'] ?? '0.0.0', '>')) {
        return $update;
    }

    foreach (($release['assets'] ?? []) as $asset) {
        if (($asset['name'] ?? '') === 'gdd-fonctions-personnalisees-v' . $version . '.zip'
            && !empty($asset['browser_download_url'])) {
            return [
                'id' => 'https://gensdegout.fr/gdd-fonctions-personnalisees/',
                'slug' => 'gdd-fonctions-personnalisees',
                'version' => $version,
                'url' => 'https://github.com/kingsaiz71/gg_custom/releases',
                'package' => esc_url_raw($asset['browser_download_url']),
                'requires' => '6.2',
                'requires_php' => '7.4',
            ];
        }
    }
    return $update;
}, 10, 4);

add_filter('auto_update_plugin', function ($update, $item) {
    if (isset($item->plugin) && $item->plugin === 'gdd-fonctions-personnalisees/gdd-fonctions-personnalisees.php') {
        return false;
    }
    return $update;
}, 10, 2);
