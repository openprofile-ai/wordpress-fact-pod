<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\AccessTokenEntity;
use wpdb;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    protected wpdb $db;
    protected string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'fact_pod_oauth_access_tokens';
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
        $this->db->insert(
            $this->table,
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
        $this->db->update(
            $this->table,
            ['revoked' => 1],
            ['access_token' => $tokenId],
            ['%d'],
            ['%s']
        );
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $revoked = $this->db->get_var($this->db->prepare(
            "SELECT revoked FROM {$this->table} WHERE access_token = %s",
            $tokenId
        ));

        return $revoked === '1';
    }
}
