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
define( 'WORDPRESS_FACT_POD_URL', plugin_dir_url( __FILE__ ) );

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

register_activation_hook(__FILE__, function () {
    update_option('wpfp_flush_rewrite', true);
});

add_action('init', function () {
    add_rewrite_endpoint('factpod', EP_ROOT | EP_PAGES);

    if (get_option('wpfp_flush_rewrite')) {
        flush_rewrite_rules();
        delete_option('wpfp_flush_rewrite');
    }
});

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
