<?php

namespace OpenProfile\WordpressFactPod\Utils;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
}