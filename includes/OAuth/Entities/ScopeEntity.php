<?php

namespace OpenProfile\WordpressFactPod\OAuth\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;

class ScopeEntity implements ScopeEntityInterface
{
    public function __construct(private string $identifier, private string $description = '', private bool $isActive = true)
    {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function jsonSerialize(): mixed
    {
        return $this->identifier;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}