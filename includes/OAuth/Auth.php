<?php

namespace OpenProfile\WordpressFactPod\OAuth;

use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Nyholm\Psr7\Response;
use OpenProfile\WordpressFactPod\OAuth\Entities\ScopeEntity;
use OpenProfile\WordpressFactPod\OAuth\Entities\UserEntity;
use OpenProfile\WordpressFactPod\OAuth\Repositories\AccessTokenRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\AuthCodeRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\ClientRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\RefreshTokenRepository;
use OpenProfile\WordpressFactPod\OAuth\Repositories\ScopeRepository;
use OpenProfile\WordpressFactPod\Utils\Http;
use OpenProfile\WordpressFactPod\Utils\Session;
use WP_Error;
use WP_REST_Request;

class Auth
{
    private const string ACCESS_TOKEN_TTL = 'PT1H'; // 1 hour
    private const string REFRESH_TOKEN_TTL = 'P1Y'; // 1 year
    
    private AuthorizationServer $server;
    private ScopeRepository $scopeRepository;

    public function __construct(private string $privateKey, private string $encryptionKey)
    {
        add_action('rest_api_init', array($this, 'init_oauth_server'));
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function init_oauth_server(): void
    {
        $clientRepository = new ClientRepository();
        $this->scopeRepository = new ScopeRepository();
        $accessTokenRepository = new AccessTokenRepository();
        $authCodeRepository = new AuthCodeRepository();
        $refreshTokenRepository = new RefreshTokenRepository();

        $this->server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $this->scopeRepository,
            $this->privateKey,
            $this->encryptionKey
        );

        $authCodeGrant = new AuthCodeGrant($authCodeRepository, $refreshTokenRepository, new DateInterval(self::ACCESS_TOKEN_TTL));
        $authCodeGrant->setRefreshTokenTTL(new DateInterval(self::REFRESH_TOKEN_TTL));
        $this->server->enableGrantType($authCodeGrant, new DateInterval(self::ACCESS_TOKEN_TTL));

        $refreshTokenGrant = new RefreshTokenGrant($refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL(new DateInterval(self::REFRESH_TOKEN_TTL));
        $this->server->enableGrantType($refreshTokenGrant, new DateInterval(self::ACCESS_TOKEN_TTL));
    }

    public function register_routes(): void
    {
        register_rest_route(
            'openprofile',
            '/oauth/authorize',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'authorize'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'openprofile',
            '/oauth/approve',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'approve'),
                'permission_callback' => array($this, 'validate_auth_request_exists'),
                'args' => array(
                    'scopes' => array(
                        'type' => 'array',
                        'description' => 'List of approved scopes',
                        'required' => true,
                    ),
                ),
                'validate_callback' => array($this, 'validate_scopes_exist'),
            )
        );

        register_rest_route(
            'openprofile',
            '/oauth/deny',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'deny'),
                'permission_callback' => array($this, 'validate_auth_request_exists'),
            )
        );

        register_rest_route(
            'openprofile',
            '/oauth/access_token',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'access_token'),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function validate_scopes_exist(WP_REST_Request $request): true|WP_Error
    {
        $scopes = $request->get_param('scopes');

        if (!is_array($scopes) || empty($scopes)) {
            return new WP_Error('invalid_scopes', 'Scopes must be a non-empty array.', array('status' => 400));
        }

        if (!$this->scopeRepository->validateScopesExist($scopes)) {
            return new WP_Error( 'invalid_scopes', 'Please provide valid scopes.', array('status' => 400));
        }

        return true;
    }

    public function validate_auth_request_exists(): bool
    {
        $data = Session::get('auth_request');

        return $data instanceof AuthorizationRequestInterface;
    }

    public function authorize(WP_REST_Request $request): \WP_REST_Response
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
        }
    }

    public function redirect_to_login(): void
    {
        wp_redirect('/openprofile/oauth/login/');

        exit;
    }

    public function approve(WP_REST_Request $request): \WP_REST_Response
    {
        if (!is_user_logged_in()) {
            $this->redirect_to_login();
        }

        $response = new Response();
        $scopes = [];

        /** @var AuthorizationRequestInterface $authRequest */
        $authRequest = Session::get('auth_request');

        $authRequest->setUser(new UserEntity(wp_get_current_user()));
        $authRequest->setAuthorizationApproved(true);

        foreach ($request->get_param('scopes') as $scope) {
            $scopes[] = new ScopeEntity($scope);
        }
        $authRequest->setScopes($scopes);

        return Http::transform_to_wp_rest_response(
            $this->server->completeAuthorizationRequest($authRequest, $response)
        );
    }

    public function deny(): \WP_REST_Response
    {
        if (!is_user_logged_in()) {
            $this->redirect_to_login();
        }

        $response = new Response();

        /** @var AuthorizationRequestInterface $authRequest */
        $authRequest = Session::get('auth_request');

        $authRequest->setUser(new UserEntity(wp_get_current_user()));
        $authRequest->setAuthorizationApproved(false);

        try {
            return Http::transform_to_wp_rest_response(
                $this->server->completeAuthorizationRequest($authRequest, $response)
            );
        } catch (OAuthServerException $exception) {
            return Http::transform_to_wp_rest_response(
                $exception->generateHttpResponse($response)
            );
        }
    }

    public function access_token(WP_REST_Request $request): \WP_REST_Response
    {
        $response = new Response();
        
        try {
            return Http::transform_to_wp_rest_response(
                $this->server->respondToAccessTokenRequest(
                    Http::transform_to_psr7_request($request),
                    $response
                )
            );
        } catch (OAuthServerException $exception) {
            return Http::transform_to_wp_rest_response(
                $exception->generateHttpResponse($response)
            );
        }
    }
}