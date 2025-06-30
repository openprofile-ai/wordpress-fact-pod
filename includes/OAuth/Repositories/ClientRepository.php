<?php

namespace OpenProfile\WordpressFactPod\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        // TODO: Implement getClientEntity() method.
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        // TODO: Implement validateClient() method.
    }
}