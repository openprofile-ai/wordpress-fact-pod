<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        // TODO: Implement getNewAuthCode() method.
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        // TODO: Implement persistNewAuthCode() method.
    }

    public function revokeAuthCode(string $codeId): void
    {
        // TODO: Implement revokeAuthCode() method.
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        // TODO: Implement isAuthCodeRevoked() method.
    }
}