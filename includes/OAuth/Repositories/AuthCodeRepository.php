<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\AuthCodeEntity;
use OpenProfile\WordpressFactPod\Utils\AbstractRepository;

class AuthCodeRepository extends AbstractRepository implements AuthCodeRepositoryInterface
{
    public function getTable(): string
    {
        return self::getPrefix() . 'oauth_auth_codes';
    }
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        self::getDB()->insert(
            $this->getTable(), 
            [
                'authorization_code' => $authCodeEntity->getIdentifier(),
                'client_id'          => $authCodeEntity->getClient()->getIdentifier(),
                'user_id'            => $authCodeEntity->getUserIdentifier(),
                'redirect_uri'       => $authCodeEntity->getRedirectUri(),
                'expires'            => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
                'scope'              => implode(' ', array_map(fn($s) => $s->getIdentifier(), $authCodeEntity->getScopes())),
            ]
        );
    }

    public function revokeAuthCode(string $codeId): void
    {
        self::getDB()->update(
            $this->getTable(),
            ['expires' => gmdate('Y-m-d H:i:s', time() - 3600)],
            ['authorization_code' => $codeId]
        );
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        $wpdb = self::getDB();
        $table = $this->getTable();

        $expires = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT expires FROM $table WHERE authorization_code = %s",
                $codeId
            )
        );

        if (!$expires) {
            return true;
        }

        return strtotime($expires) < time();
    }
}