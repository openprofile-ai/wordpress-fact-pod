<?php

namespace OpenProfile\WordpressFactPod\OAuth;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Nyholm\Psr7\Response;
use OpenProfile\WordpressFactPod\OAuth\Entities\UserEntity;
use OpenProfile\WordpressFactPod\OAuth\Repositories\AccessTokenRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\AuthCodeRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\ClientRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\RefreshTokenRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\ScopeRepository;
use OpenProfile\WordpressFactPod\Utils\Http;
use OpenProfile\WordpressFactPod\Utils\Session;

class Auth
{
    private AuthorizationServer $server;

    public function __construct(private string $privateKey, private string $encryptionKey)
    {
        add_action('rest_api_init', array($this, 'init_oauth_server'));
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_login', array($this, 'redirect_to_scopes'));
    }

    public function init_oauth_server(): void
    {
        $clientRepository = new ClientRepository();
        $scopeRepository = new ScopeRepository();
        $accessTokenRepository = new AccessTokenRepository();
        $authCodeRepository = new AuthCodeRepository();
        $refreshTokenRepository = new RefreshTokenRepository();


        // Setup the authorization server
        $this->server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $this->privateKey,
            $this->encryptionKey
        );

        $grant = new AuthCodeGrant(
            $authCodeRepository,
            $refreshTokenRepository,
            new \DateInterval('PT10M')
        );

        $grant->setRefreshTokenTTL(new \DateInterval('P1Y')); // refresh tokens will expire after 1 year

        $this->server->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );
    }

    public function register_routes(): void
    {
        register_rest_route(
            'openprofile/oauth',
            '/authorize',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'authorize'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'openprofile/oauth',
            '/approve',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'approve'),
                'permission_callback' => array( $this, 'check_auth_request' ),
                'args'                => array(
                    'scopes'   => array(
                        'type'        => 'array',
                        'description' => 'List of approved scopes',
                        'required'    => true,
                    ),
                ),
                'validate_callback' => array($this, 'validate_scopes_exist'),
            )
        );

        register_rest_route(
            'openprofile/oauth',
            '/deni',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'deni'),
                'permission_callback' => array( $this, 'check_auth_request' ),
            )
        );

        register_rest_route(
            'openprofile/oauth',
            '/token',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'token'),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function check_auth_request(): bool {
        $data = Session::get('auth_request');

        return $data instanceof AuthorizationRequestInterface;
    }

    public function authorize(\WP_REST_Request $request)
    {
        $response = new Response();

        try {
            $authRequest = $this->server->validateAuthorizationRequest(
                Http::transform_to_psr7_request($request)
            );

            Session::put('auth_request', $authRequest);
            $this->redirect_to_login();

            exit;
        } catch (OAuthServerException $exception) {
            return Http::transform_to_wp_rest_response(
                $exception->generateHttpResponse($response)
            );

        } catch (\Exception $exception) {
            return new \WP_Error(
                'openprofile_oauth_authorize_error',
                $exception->getMessage(),
                array('status' => 500)
            );
        }
    }

    public function redirect_to_login(): void
    {
        wp_redirect('/openprofile/oauth/login/');

        exit;
    }

    public function redirect_to_scopes(): void
    {
        wp_redirect('/openprofile/oauth/scopes');

        exit;
    }

    public function approve(\WP_REST_Request $request)
    {
        if (!is_user_logged_in()) {
            $this->redirect_to_login();
        }

        $response = new Response();

        /** @var AuthorizationRequestInterface $authRequest */
        $authRequest = Session::get('auth_request');

        $authRequest->setUser(new UserEntity(wp_get_current_user()));
        $authRequest->setAuthorizationApproved(true);
        $authRequest->setScopes();

        // TODO Store in the DB approved OpenProfile permissions

        return Http::transform_to_wp_rest_response(
            $this->server->completeAuthorizationRequest($authRequest, $response)
        );
    }

    public function deni()
    {
        if (!is_user_logged_in()) {
            $this->redirect_to_login();
        }

        $response = new Response();

        /** @var AuthorizationRequestInterface $authRequest */
        $authRequest = Session::get('auth_request');

        $authRequest->setUser(new UserEntity(wp_get_current_user()));
        $authRequest->setAuthorizationApproved(false);

        return Http::transform_to_wp_rest_response(
            $this->server->completeAuthorizationRequest($authRequest, $response)
        );
    }

    public function validate_scopes_exist($value, $request, $param) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fact_pod_oauth_scopes';

        if (!is_array($value) || empty($value)) {
            return new \WP_Error('invalid_scopes', 'Scopes must be a non-empty array.', array('status' => 400));
        }

        // Prepare for SQL IN clause
        $placeholders = implode(',', array_fill(0, count($value), '%s'));
        $query = "SELECT scope FROM $table_name WHERE scope IN ($placeholders)";
        $results = $wpdb->get_col($wpdb->prepare($query, $value));

        // Find missing scopes
        $missing = array_diff($value, $results);

        if (!empty($missing)) {
            return new \WP_Error(
                'invalid_scopes',
                'The following scopes do not exist: ' . implode(', ', $missing),
                array('status' => 400)
            );
        }

        return true;
    }
}