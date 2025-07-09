<?php
/**
 * Template for /openprofile/oauth/scopes
 */

use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use OpenProfile\WordpressFactPod\Utils\Session;

defined('ABSPATH') || exit;

include WORDPRESS_FACT_POD_PATH . 'templates/header-factpod.php';

/** @var AuthorizationRequestInterface $authRequest */
$authRequest = Session::get('auth_request');

/** @var WP_User $user */
$user = wp_get_current_user();

if (is_null($authRequest) || is_null($user)) {
    wp_redirect('/');
    exit;
}
?>
    <div class="fact-pod-form">
        <h2>Sign up to <?php echo $authRequest->getClient()->getName() ?></h2>

        <?php foreach ($authRequest->getScopes() as $scope) { ?>
            <label><input type="checkbox" value="<?php echo $scope->getIdentifier() ?>" /> <?php echo $scope->getDescription() ?></label>
        <?php } ?>
    </div>

<?php
include WORDPRESS_FACT_POD_PATH . 'templates/footer-factpod.php';