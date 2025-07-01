<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use OpenProfile\WordpressFactPod\OAuth\Entities\AuthCodeEntity;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fact_pod_oauth_auth_codes';

        $wpdb->insert($table, [
            'authorization_code' => $authCodeEntity->getIdentifier(),
            'client_id'          => $authCodeEntity->getClient()->getIdentifier(),
            'user_id'            => $authCodeEntity->getUserIdentifier(),
            'redirect_uri'       => $authCodeEntity->getRedirectUri(),
            'expires'            => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'scope'              => implode(' ', array_map(fn($s) => $s->getIdentifier(), $authCodeEntity->getScopes())),
        ]);
    }

    public function revokeAuthCode(string $codeId): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fact_pod_oauth_auth_codes';

        $wpdb->update(
            $table,
            ['expires' => gmdate('Y-m-d H:i:s', time() - 3600)],
            ['authorization_code' => $codeId]
        );
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fact_pod_oauth_auth_codes';

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