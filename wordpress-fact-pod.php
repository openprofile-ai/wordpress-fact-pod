<?php
/**
 * Plugin Name: OpenProfile Wordpress Fact Pod Plugin
 * Description: Connecting to the MCP.
 * Version: 1.0
 * Author: OpenProfile Team
 */

defined('ABSPATH') || exit;

// Check if Composer autoloader exists.
if (!file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    wp_die(
        sprintf(
            'Please run <code>composer install</code> in the plugin directory: <code>%s</code>',
            esc_html(plugin_dir_path(__FILE__))
        )
    );
}

// Load Composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Set custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Custom error handler: $errstr in $errfile on line $errline");
    }
    return true;
}, E_USER_NOTICE);

// Initialize the plugin
function wordpress_fact_pod_init() {
    return \OpenProfile\WordpressFactPod\WordpressFactPod::get_instance();
}

// Start the plugin
wordpress_fact_pod_init();