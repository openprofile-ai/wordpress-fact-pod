<?php

namespace OpenProfile\WordpressFactPod\Utils;

use OpenProfile\WordpressFactPod\OAuth\Repositories\ScopeRepository;
use OpenProfile\WordpressFactPod\WordpressFactPod;

class WellKnown
{
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

    public static function generateOpenProfileDiscovery(string $baseUrl): array
    {
        // Ensure the base URL doesn't end with a slash
        $baseUrl = rtrim($baseUrl, '/');
        $categories = WooCommerce::getTopLevelCategories();
        $shopUrl = WooCommerce::getShopUrl($baseUrl);
        $catalogItems = [];

        // Map categories to OfferCatalog items
        foreach ($categories as $cat) {
            $catalogItems[] = [
                '@type' => 'OfferCatalog',
                'name' => $cat['name'] ?? '',
                'url' => $cat['url'] ?? '',
                'description' => $cat['description'] ?? '',
            ];
        }

        return [
            'openprofile' => [
                'version' => WordpressFactPod::VERSION,
            ],
            'factpod' => [
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => get_bloginfo('name'),
                'description'     => get_bloginfo('description'),
                'url'      => $baseUrl,
                'hasPart' => [
                    '@type' => 'OfferCatalog',
                    'name'  => 'Product Categories',
                    'url'   => $shopUrl,
                    'itemListElement' => $catalogItems,
                ]
            ],
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