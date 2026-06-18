<?php

declare(strict_types=1);

namespace App\OAuth\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * A lightweight, non-persisted OAuth scope. Serializes to its bare identifier
 * so it lands in the access-token JWT `scopes` claim as a plain string.
 */
final readonly class Scope implements ScopeEntityInterface
{
    public function __construct(private string $identifier)
    {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function jsonSerialize(): string
    {
        return $this->identifier;
    }
}
