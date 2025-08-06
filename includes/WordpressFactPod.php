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

        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants(): void
    {
        if (!defined('WORDPRESS_FACT_POD_VERSION')) {
            define('WORDPRESS_FACT_POD_VERSION', self::VERSION);
        }

        if (!defined('WORDPRESS_FACT_POD_PATH')) {
            define('WORDPRESS_FACT_POD_PATH', $this->pluginPath);
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void
    {
        // Activation hooks - we need to use the main plugin file
        register_activation_hook($this->pluginPath . 'wordpress-fact-pod.php', [$this, 'activate']);

        // Init hooks
        add_action('init', [$this, 'init']);
        
        // Query vars
        add_filter('query_vars', [$this, 'register_query_vars']);
        
        // Template redirect
        add_action('template_redirect', [$this, 'handle_template_redirect']);
        
        // Admin menu
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
        $this->install();
        $this->add_category_oauth_scopes();
        $this->generate_keys();
        
        update_option('wpfp_flush_rewrite', true);
        flush_rewrite_rules();
    }

    /**
     * Install database tables
     */
    private function install(): void
    {
        require_once $this->pluginPath . 'install.php';
        wp_fact_pod_install();
    }

    /**
     * Add OAuth scopes
     */
    private function add_category_oauth_scopes(): void
    {
        require_once $this->pluginPath . 'install.php';
        add_category_oauth_scopes();
    }

    /**
     * Generate keys
     */
    private function generate_keys(): void
    {
        require_once $this->pluginPath . 'install.php';
        wp_fact_pod_generate_keys();
    }

    /**
     * Initialize plugin
     */
    public function init(): void
    {
        // Start session
        new Session();
        
        // Initialize OAuth
        $this->init_oauth();
        
        // Add rewrite rules
        $this->add_rewrite_rules();
        
        // Load user options if user is logged in
        $this->load_user_options();
    }

    /**
     * Initialize OAuth
     */
    private function init_oauth(): void
    {
        $privateKey = $this->pluginPath . 'private.key';
        $publicKey = $this->pluginPath . 'public.key';

        if (file_exists($privateKey) && file_exists($publicKey)) {
            new Auth($privateKey, $publicKey);
        } else {
            error_log('[FactPod] Missing private or public key at init.');
        }
    }

    /**
     * Add rewrite rules
     */
    private function add_rewrite_rules(): void
    {
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
    }

    /**
     * Register query vars
     */
    public function register_query_vars($vars): array
    {
        $vars[] = 'wpfp_oauth_login';
        $vars[] = 'wpfp_oauth_scopes';
        return $vars;
    }

    /**
     * Handle template redirect
     */
    public function handle_template_redirect(): void
    {
        if (get_query_var('wpfp_oauth_login')) {
            include $this->pluginPath . 'templates/oauth-login.php';
            exit;
        }
        if (get_query_var('wpfp_oauth_scopes')) {
            include $this->pluginPath . 'templates/oauth-scopes.php';
            exit;
        }
    }

    /**
     * Load user options if user is logged in
     */
    private function load_user_options(): void
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