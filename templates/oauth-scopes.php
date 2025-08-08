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

<div class="fact-pod oauth-scopes box">
    <div class="logo">
    </div>
    <h2>Sign up to <?php echo $authRequest->getClient()->getName() ?></h2>
    
    <p class="oauth-explanation">You are about to provide OAuth permissions to access your data. Please review the requested permissions below before proceeding.</p>

    <!-- Approve Form -->
    <form action="/wp-json/openprofile/oauth/approve" class="fact-pod-form" method="post" id="approve-form">
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wp_rest'); ?>" />
        <?php foreach ($authRequest->getScopes() as $scope) { ?>
            <label>
                <input type="checkbox" checked value="<?php echo $scope->getIdentifier() ?>" name="scopes[]" />
                <?php echo $scope->getDescription() ?>
            </label>
        <?php } ?>
    </form>

    <!-- Deny Form -->
    <form action="/wp-json/openprofile/oauth/deny" method="post" id="deny-form">
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wp_rest'); ?>" />
    </form>

    <!-- Buttons Layer -->
    <div class="form-actions">
        <button type="submit" form="approve-form" class="button approve-form">Approve</button>
        <button type="submit" form="deny-form" class="button deny-form">Deny</button>
    </div>
</div>

<?php
include WORDPRESS_FACT_POD_PATH . 'templates/footer-factpod.php';