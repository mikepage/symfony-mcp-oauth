<?php

declare(strict_types=1);

namespace App\OAuth\Entity;

use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * Bridges the authenticated Symfony user identifier (the user's email) into the
 * league authorization request. The identifier lands in the access-token `sub`.
 */
final readonly class UserEntity implements UserEntityInterface
{
    public function __construct(private string $identifier)
    {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
