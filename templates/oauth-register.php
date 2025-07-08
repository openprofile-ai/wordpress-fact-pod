<?php
/**
 * Template for /openprofile/oauth/register
 */
defined('ABSPATH') || exit;

include WORDPRESS_FACT_POD_PATH . 'templates/header-factpod.php';
?>
    <style>
        header .main-navigation, .main-header,
        .site-header .menu, .screen-reader-text.skip-link,
        nav,
        .primary-menu {
            display: none !important;
        }

    </style>
    <br>
    <br>
    <br>
    <div class="wrap">
        <?php echo do_shortcode('[woocommerce_my_account]'); ?>
    </div>

<?php
include WORDPRESS_FACT_POD_PATH . 'templates/footer-factpod.php';