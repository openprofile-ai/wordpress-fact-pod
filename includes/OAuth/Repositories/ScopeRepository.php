<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        // TODO: Implement getScopeEntityByIdentifier() method.
    }

    public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, ?string $userIdentifier = null, ?string $authCodeId = null): array
    {
        // TODO: Implement finalizeScopes() method.
    }
}