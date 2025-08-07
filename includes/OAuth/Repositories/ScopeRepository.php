<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\ScopeEntity;

class ScopeRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fact_pod_oauth_scopes';

        $scopeRow = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE scope = %s AND is_active=1", $identifier)
        );

        if (!$scopeRow) {
            return null;
        }

        return new ScopeEntity($scopeRow->scope, $scopeRow->description);
    }

    public function validateScopesExist(array $scopes = []): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fact_pod_oauth_scopes';

        $placeholders = implode(',', array_fill(0, count($scopes), '%s'));
        $query = "SELECT scope FROM $table WHERE scope IN ($placeholders)";

        $results = $wpdb->get_col($wpdb->prepare($query, $scopes));
        $missing = array_diff($scopes, $results);

        return empty($missing);
    }

    public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, ?string $userIdentifier = null, ?string $authCodeId = null): array
    {
        // Example: Allow all requested scopes as is.
        // You can add custom logic here, for example:
        // - Enforce client-specific scope restrictions
        // - Enforce user-specific scope restrictions
        // - Add default scopes if none requested

        // For now, just return scopes as-is
        return $scopes;
    }
}