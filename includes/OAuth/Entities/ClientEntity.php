<?php

namespace OpenProfile\WordpressFactPod\OAuth\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use ClientTrait, EntityTrait;

    public function __construct(string $identifier, string $name, string $redirectUri)
    {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->isConfidential = true;
    }
}