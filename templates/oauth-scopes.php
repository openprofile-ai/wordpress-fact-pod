<?php
/**
 * Template for /openprofile/oauth/scopes
 */

use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use OpenProfile\WordpressFactPod\Utils\Session;

defined('ABSPATH') || exit;

/** @var AuthorizationRequestInterface $authRequest */
$authRequest = Session::get('auth_request');

/** @var WP_User $user */
$user = wp_get_current_user();

if (is_null($authRequest) || is_null($user)) {
    wp_redirect('/');
    exit;
}

include WORDPRESS_FACT_POD_PATH . 'templates/header-factpod.php';
?>


<div class="fact-pod-form box">
    <div class="logo">
    </div>
    <h2>Sign up to <?php echo $authRequest->getClient()->getName() ?></h2>
    <form action="/wp-json/openprofile/oauth/approve" class="fact-pod-form" method="post" id="oauth-scopes-form">
        <?php foreach ($authRequest->getScopes() as $scope) { ?>
            <label><input type="checkbox" value="<?php echo $scope->getIdentifier() ?>" name="scopes[]" /> <?php echo $scope->getDescription() ?></label>
        <?php } ?>
        <p>
            <button type="submit" class="button">Approve</button>
            <a href="/openprofile/oauth/login/" class="button">Cancel</a>
        </p>
    </form>
</div>

<?php
include WORDPRESS_FACT_POD_PATH . 'templates/footer-factpod.php';