<?php
/**
 * Template for /openprofile/oauth/register
 */

use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use OpenProfile\WordpressFactPod\Utils\Session;

defined('ABSPATH') || exit;

include WORDPRESS_FACT_POD_PATH . 'templates/header-factpod.php';

/** @var AuthorizationRequestInterface $authRequest */
$authRequest = Session::get('auth_request');

if (is_null($authRequest)) {
    wp_redirect('/');
    exit;
}
?>
    <div class="fact-pod-form">
        <h2>Sign up to <?php echo $authRequest->getClient()->getName() ?></h2>
        <?php woocommerce_login_form(); ?>
    </div>

<?php
include WORDPRESS_FACT_POD_PATH . 'templates/footer-factpod.php';