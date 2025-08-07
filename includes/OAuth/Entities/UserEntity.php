<?php

namespace OpenProfile\WordpressFactPod\OAuth\Entities;

use League\OAuth2\Server\Entities\UserEntityInterface;
use WP_User;

class UserEntity implements UserEntityInterface
{
    public function __construct(public WP_User $user)
    {}

    public function getIdentifier(): string
    {
        return $this->user->ID;
    }
}