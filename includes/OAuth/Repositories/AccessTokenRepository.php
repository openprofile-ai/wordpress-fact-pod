<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\AccessTokenEntity;
use OpenProfile\WordpressFactPod\Utils\AbstractRepository;

class AccessTokenRepository extends AbstractRepository implements AccessTokenRepositoryInterface
{
    public function getTable(): string
    {
        return self::getPrefix() . 'oauth_access_tokens';
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);

        foreach ($scopes as $scope) {
            if ($scope instanceof ScopeEntityInterface) {
                $accessToken->addScope($scope);
            }
        }

        if ($userIdentifier !== null) {
            $accessToken->setUserIdentifier($userIdentifier);
        }

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        self::getDB()->insert(
            $this->getTable(),
            [
                'access_token' => $accessTokenEntity->getIdentifier(),
                'client_id'    => $accessTokenEntity->getClient()->getIdentifier(),
                'user_id'      => $accessTokenEntity->getUserIdentifier(),
                'revoked'      => 0,
                'expires'      => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
                'scope'        => implode(' ', array_map(fn($scope) => $scope->getIdentifier(), $accessTokenEntity->getScopes())),
            ],
            [
                '%s', '%s', '%s', '%d', '%s', '%s'
            ]
        );
    }

    public function revokeAccessToken(string $tokenId): void
    {
        self::getDB()->update(
            $this->getTable(),
            ['revoked' => 1],
            ['access_token' => $tokenId],
            ['%d'],
            ['%s']
        );
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $wpdb = self::getDB();
        $table = $this->getTable();
        
        $revoked = $wpdb->get_var($wpdb->prepare(
            "SELECT revoked FROM {$table} WHERE access_token = %s",
            $tokenId
        ));

        return $revoked === '1';
    }
}
