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

    public function __construct(string $privateKey, string $encryptionKey)
    {
        $this->init_oauth_server($privateKey, $encryptionKey);

        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('rest_authentication_errors', array($this, 'authenticate_request'));
    }

    public function init_oauth_server(string $privateKey, string $encryptionKey): void
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
            $privateKey,
            $encryptionKey
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

            if (!is_user_logged_in()) {
                $this->redirect_to_login();
            } else {
                $this->redirect_to_scopes();
            }

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
        add_action('wp_login', array($this, 'redirect_to_scopes'));
        wp_redirect(wp_login_url());

        exit;
    }

    public function redirect_to_scopes(): void
    {
        wp_redirect('/openprofile/oauth/scopes');

        exit;
    }

    public function approve()
    {
        if (!is_user_logged_in()) {
            $this->redirect_to_login();
        }

        $response = new Response();

        /** @var AuthorizationRequestInterface $authRequest */
        $authRequest = Session::get('auth_request');

        $authRequest->setUser(new UserEntity(wp_get_current_user()));
        $authRequest->setAuthorizationApproved(true);

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
}