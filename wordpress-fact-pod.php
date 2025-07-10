<?php
/**
 * Plugin Name: OpenProfile Wordpress Fact Pod Plugin
 * Description: Connecting to the MCP.
 * Version: 1.0
 * Author: OpenProfile Team
 */

defined('ABSPATH') || exit;

const WORDPRESS_FACT_POD_VERSION = '0.0.1';
define( 'WORDPRESS_FACT_POD_PATH', plugin_dir_path( __FILE__ ) );

// Check if Composer autoloader exists.
if ( ! file_exists( WORDPRESS_FACT_POD_PATH . 'vendor/autoload.php' ) ) {
    wp_die(
        sprintf(
            'Please run <code>composer install</code> in the plugin directory: <code>%s</code>',
            esc_html( WORDPRESS_FACT_POD_PATH )
        )
    );
}

require_once WORDPRESS_FACT_POD_PATH . 'vendor/autoload.php';

register_activation_hook(__FILE__, 'wp_fact_pod_install');
register_activation_hook(__FILE__, 'add_category_oauth_scopes');

function wpfp_on_plugin_activation() {
    wp_fact_pod_install();
    add_category_oauth_scopes();
    update_option('wpfp_flush_rewrite', true);
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wpfp_on_plugin_activation');

add_action('init', function () {
    add_rewrite_rule(
        '^openprofile/oauth/login/?$',
        'index.php?wpfp_oauth_login=1',
        'top'
    );

    add_rewrite_rule(
        '^openprofile/oauth/scopes/?$',
        'index.php?wpfp_oauth_scopes=1',
        'top'
    );

    if (get_option('wpfp_flush_rewrite')) {
        flush_rewrite_rules();
        delete_option('wpfp_flush_rewrite');
    }
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'wpfp_oauth_login';
    $vars[] = 'wpfp_oauth_scopes';
    return $vars;
});

add_action('template_redirect', function () {
    if (get_query_var('wpfp_oauth_login')) {
        include WORDPRESS_FACT_POD_PATH . 'templates/oauth-login.php';
        exit;
    }
    if (get_query_var('wpfp_oauth_scopes')) {
        include WORDPRESS_FACT_POD_PATH . 'templates/oauth-scopes.php';
        exit;
    }
});

// Start WP session
new \OpenProfile\WordpressFactPod\Utils\Session();

// Start Auth
add_action('init', function () {
    $privateKey = WORDPRESS_FACT_POD_PATH . 'private.key';
    $publicKey  = WORDPRESS_FACT_POD_PATH . 'public.key';

    if (file_exists($privateKey) && file_exists($publicKey)) {
        new \OpenProfile\WordpressFactPod\OAuth\Auth($privateKey, $publicKey);
    } else {
        error_log('[FactPod] Missing private or public key at init.');
    }
});
add_action('admin_menu', 'wpfp_add_admin_menu');

function wpfp_add_admin_menu() {
    add_menu_page(
        'Fact Pod Settings',
        'Fact Pod',
        'manage_options',
        'wpfp_fact_pod',
        'wpfp_settings_page_html',
        'dashicons-admin-generic',
        56
    );
}

if (is_admin()) {
    require_once WORDPRESS_FACT_POD_PATH . 'src/admin/settings-page.php';
}

if (!is_admin()) {
    add_action('init', function () {
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            require_once WORDPRESS_FACT_POD_PATH . 'src/user/user-options.php';
        }
    });
}

function fact_pod_enqueue_styles() {
    wp_enqueue_style('fact-pod-styles', plugin_dir_url(__FILE__) . 'assets/styles/fact-pod.css');
}

add_action('wp_enqueue_scripts', 'fact_pod_enqueue_styles');