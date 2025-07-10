<?php
/**
 * Template for /openprofile/oauth/register
 */

use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use OpenProfile\WordpressFactPod\Utils\Session;

defined('ABSPATH') || exit;

/** @var AuthorizationRequestInterface $authRequest */
$authRequest = Session::get('auth_request');

if (is_null($authRequest)) {
    wp_redirect('/');
    exit;
}

/** @var WP_User $user */
$user = wp_get_current_user();

if (! is_null($user) && $user->ID > 0) {
    wp_redirect('/openprofile/oauth/scopes/');
    exit;
}

include WORDPRESS_FACT_POD_PATH . 'templates/header-factpod.php';
?>

<div class="fact-pod-form box">
    <div class="logo">
    </div>
    <h2>Sign up to <?php echo $authRequest->getClient()->getName() ?></h2>
    <p>Use your <?php echo get_option('blogname'); ?> account</p>
    <?php woocommerce_login_form(); ?>
</div>

<?php
include WORDPRESS_FACT_POD_PATH . 'templates/footer-factpod.php';