<?php

namespace OpenProfile\WordpressFactPod;

use OpenProfile\WordpressFactPod\OAuth\Auth;
use OpenProfile\WordpressFactPod\Utils\Session;

/**
 * Main plugin class that initializes all components
 */
class WordpressFactPod
{
    /**
     * Plugin version
     */
    const VERSION = '0.0.1';

    /**
     * Plugin path
     */
    private string $pluginPath;

    /**
     * Plugin URL
     */
    private string $pluginUrl;

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class
     *
     * @return WordpressFactPod
     */
    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->pluginPath = plugin_dir_path(dirname(__FILE__));
        $this->pluginUrl = plugin_dir_url(dirname(__FILE__));

        if (!defined('WORDPRESS_FACT_POD_VERSION')) {
            define('WORDPRESS_FACT_POD_VERSION', self::VERSION);
        }

        if (!defined('WORDPRESS_FACT_POD_PATH')) {
            define('WORDPRESS_FACT_POD_PATH', $this->pluginPath);
        }

        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init(): void
    {
        // Activation hooks
        register_activation_hook($this->pluginPath . 'wordpress-fact-pod.php', [$this, 'activate']);

        // Start session
        new Session();

        // Initialize OAuth
        $this->init_oauth();

        // Load user options if user is logged in
        $this->add_user_options();

        // Add section to admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Plugin activation
     */
    public function activate(): void
    {
        require_once $this->pluginPath . 'install.php';

        wp_fact_pod_install(self::VERSION);
        wp_fact_pod_generate_keys();

        update_option('wpfp_flush_rewrite', true);
        flush_rewrite_rules();
    }

    /**
     * Initialize OAuth
     */
    private function init_oauth(): void
    {
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
                include $this->pluginPath . 'templates/oauth-login.php';
                exit;
            }
            if (get_query_var('wpfp_oauth_scopes')) {
                include $this->pluginPath . 'templates/oauth-scopes.php';
                exit;
            }
        });

        add_action('init', function () {
            $privateKey = $this->pluginPath . 'private.key';
            $publicKey = $this->pluginPath . 'public.key';

            if (file_exists($privateKey) && file_exists($publicKey)) {
                new Auth($privateKey, $publicKey);
            } else {
                error_log('[FactPod] Missing private or public key at init.');
            }
        });

        add_action('wp_login', function () {
            wp_redirect('/openprofile/oauth/scopes/');
            exit;
        });
    }

    /**
     * Load user options if user is logged in
     */
    private function add_user_options(): void
    {
        if (!is_admin() && function_exists('is_user_logged_in') && is_user_logged_in()) {
            require_once $this->pluginPath . 'src/user/user-options.php';
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void
    {
        add_menu_page(
            'Fact Pod Settings',
            'Fact Pod',
            'manage_options',
            'wpfp_fact_pod',
            'wpfp_settings_page_html',
            'dashicons-admin-generic',
            56
        );

        if (is_admin()) {
            require_once $this->pluginPath . 'src/admin/settings-page.php';
        }
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style('fact-pod-styles', $this->pluginUrl . 'assets/styles/fact-pod.css');
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts(): void
    {
        wp_register_script(
            'fact-pod-script',
            $this->pluginUrl . 'assets/js/fact-pod.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_enqueue_script('fact-pod-script');
    }
}