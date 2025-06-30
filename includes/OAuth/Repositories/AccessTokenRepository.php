<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        // TODO: Implement getNewToken() method.
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        // TODO: Implement persistNewAccessToken() method.
    }

    public function revokeAccessToken(string $tokenId): void
    {
        // TODO: Implement revokeAccessToken() method.
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        // TODO: Implement isAccessTokenRevoked() method.
    }
}