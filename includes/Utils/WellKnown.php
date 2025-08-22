<?php

namespace OpenProfile\WordpressFactPod\Utils;

use OpenProfile\WordpressFactPod\OAuth\Repositories\ScopeRepository;

class WellKnown
{
    /**
     * Generate a JWK Set from a public key
     *
     * @param string $publicKeyPath Path to the public key file
     * @return array The JWK Set
     */
    public static function generateJwks(string $publicKeyPath): array
    {
        // Read the public key
        $publicKey = file_get_contents($publicKeyPath);
        if (!$publicKey) {
            return ['keys' => []];
        }

        // Parse the public key
        $keyResource = openssl_pkey_get_public($publicKey);
        if (!$keyResource) {
            return ['keys' => []];
        }

        // Get the key details
        $keyDetails = openssl_pkey_get_details($keyResource);
        if (!$keyDetails || !isset($keyDetails['rsa']) || !isset($keyDetails['rsa']['n'])) {
            return ['keys' => []];
        }

        // Convert the modulus to base64url encoding
        $modulus = base64_encode($keyDetails['rsa']['n']);
        $modulus = str_replace(['+', '/', '='], ['-', '_', ''], $modulus);

        // Create the JWK
        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'kid' => 'openprofile-key-1',
                    'alg' => 'RS256',
                    'n' => $modulus,
                    'e' => 'AQAB', // Standard RSA exponent in base64url encoding
                ]
            ]
        ];
    }

    /**
     * Generate the OpenProfile discovery document
     *
     * @param string $baseUrl The base URL of the WordPress site
     * @return array The discovery document
     */
    public static function generateOpenProfileDiscovery(string $baseUrl): array
    {
        // Ensure the base URL doesn't end with a slash
        $baseUrl = rtrim($baseUrl, '/');

        return [
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl . '/wp-json/openprofile/oauth/authorize',
            'token_endpoint' => $baseUrl . '/wp-json/openprofile/oauth/access_token',
            'registration_endpoint' => $baseUrl . '/wp-json/openprofile/oauth/register',
            'jwks_uri' => $baseUrl . '/.well-known/openprofile-jwks.json',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'scopes_supported' => (new ScopeRepository())->getSupportedScopes(),
        ];
    }
}