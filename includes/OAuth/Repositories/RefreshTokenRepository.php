<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\RefreshTokenEntity;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fact_pod_oauth_refresh_tokens';

        $wpdb->insert(
            $table,
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
        global $wpdb;
        $table = $wpdb->prefix . 'fact_pod_oauth_refresh_tokens';

        $wpdb->update(
            $table,
            ['revoked' => 1],
            ['refresh_token' => $tokenId],
            ['%d'],
            ['%s']
        );
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fact_pod_oauth_refresh_tokens';

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