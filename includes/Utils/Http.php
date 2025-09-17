<?php

namespace OpenProfile\WordpressFactPod\Utils;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\ResourceServer;
use OpenProfile\WordpressFactPod\OAuth\Repositories\AccessTokenRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Http
{
    public static function transform_to_psr7_request(WP_REST_Request $wpRequest): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $uri = $psr17Factory->createUri(rest_url($wpRequest->get_route()));
        $psrRequest = $psr17Factory->createServerRequest(
            $wpRequest->get_method(),
            $uri,
            $_SERVER
        );

        foreach ($wpRequest->get_headers() as $name => $values) {
            foreach ($values as $value) {
                $psrRequest = $psrRequest->withAddedHeader($name, $value);
            }
        }

        $body = $psr17Factory->createStream($wpRequest->get_body());
        $psrRequest = $psrRequest->withBody($body);

        $psrRequest = $psrRequest->withQueryParams($wpRequest->get_query_params());
        $psrRequest = $psrRequest->withParsedBody($wpRequest->get_body_params());

        foreach ($wpRequest->get_attributes() as $key => $value) {
            $psrRequest = $psrRequest->withAttribute($key, $value);
        }

        return $psrRequest;
    }

    public static function transform_to_wp_rest_response(ResponseInterface $psrResponse): WP_REST_Response
    {
        $status = $psrResponse->getStatusCode();
        $headers = [];

        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        $body = (string) $psrResponse->getBody();

        // Decode JSON if applicable
        $decodedBody = json_decode($body, true);
        $responseBody = json_last_error() === JSON_ERROR_NONE ? $decodedBody : $body;

        return new WP_REST_Response($responseBody, $status, $headers);
    }

    /**
     * Validate Bearer token and resolve current user.
     * Returns WP_User on success or WP_Error on failure (status 401 by default).
     */
    public static function authenticate(WP_REST_Request $request): \WP_User|WP_Error
    {
        try {
            $publicKeyPath = defined('WORDPRESS_FACT_POD_PATH')
                ? WORDPRESS_FACT_POD_PATH . 'public.key'
                : plugin_dir_path(dirname(__FILE__, 2)) . 'public.key';

            $server = new ResourceServer(new AccessTokenRepository(), $publicKeyPath);

            $psrRequest = self::transform_to_psr7_request($request);
            $validated  = $server->validateAuthenticatedRequest($psrRequest);

            $userId = (int) ($validated->getAttribute('oauth_user_id') ?? 0);
            if (!$userId) {
                return new WP_Error('unauthorized', 'Missing user in access token', ['status' => 401]);
            }

            $user = get_user_by('id', $userId);
            if (!$user) {
                return new WP_Error('unauthorized', 'User not found', ['status' => 401]);
            }

            wp_set_current_user($user->ID);

            return $user;
        } catch (\Throwable $e) {
            return new WP_Error('invalid_token', $e->getMessage(), ['status' => 401]);
        }
    }
}