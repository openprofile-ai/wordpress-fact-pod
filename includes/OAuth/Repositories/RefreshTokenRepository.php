<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\RefreshTokenEntity;
use OpenProfile\WordpressFactPod\Utils\AbstractRepository;

class RefreshTokenRepository extends AbstractRepository implements RefreshTokenRepositoryInterface
{
    public function getTable(): string
    {
        return self::getPrefix() . 'oauth_refresh_tokens';
    }
    
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        self::getDB()->insert(
            $this->getTable(),
            [
                'refresh_token' => $refreshTokenEntity->getIdentifier(),
                'access_token' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
                'revoked'       => 0,
                'expires'       => $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            ],
            ['%s', '%s', '%d', '%s']
        );
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        self::getDB()->update(
            $this->getTable(),
            ['revoked' => 1],
            ['refresh_token' => $tokenId],
            ['%d'],
            ['%s']
        );
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $wpdb = self::getDB();
        $table = $this->getTable();

        $revoked = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT revoked FROM $table WHERE refresh_token = %s",
                $tokenId
            )
        );

        if ($revoked === null) {
            return true;
        }

        return (bool)$revoked;
    }
}