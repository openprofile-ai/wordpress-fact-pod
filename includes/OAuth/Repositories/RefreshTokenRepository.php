<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        // TODO: Implement getNewRefreshToken() method.
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        // TODO: Implement persistNewRefreshToken() method.
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        // TODO: Implement revokeRefreshToken() method.
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        // TODO: Implement isRefreshTokenRevoked() method.
    }
}