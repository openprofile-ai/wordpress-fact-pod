<?php
/**
 * Template for /openprofile/oauth/register
 */
defined('ABSPATH') || exit;

include WORDPRESS_FACT_POD_PATH . 'templates/header-factpod.php';
?>
    <div class="fact-pod-form">
        <h2>User Authentication</h2>
        <h1>AUTH</h1>
        <?php woocommerce_login_form(); ?>
    </div>

<?php
include WORDPRESS_FACT_POD_PATH . 'templates/footer-factpod.php';