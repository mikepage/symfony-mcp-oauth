<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

/**
 * A refresh token tied to the access token it was issued alongside.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oauth_refresh_token')]
class OAuthRefreshToken implements RefreshTokenEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 80)]
    private string $identifier;

    #[ORM\ManyToOne(targetEntity: OAuthAccessToken::class)]
    #[ORM\JoinColumn(name: 'access_token_identifier', referencedColumnName: 'identifier', nullable: false, onDelete: 'CASCADE')]
    private OAuthAccessToken $accessToken;

    #[ORM\Column]
    private \DateTimeImmutable $expiryDateTime;

    #[ORM\Column]
    private bool $revoked = false;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getExpiryDateTime(): \DateTimeImmutable
    {
        return $this->expiryDateTime;
    }

    public function setExpiryDateTime(\DateTimeImmutable $dateTime): void
    {
        $this->expiryDateTime = $dateTime;
    }

    public function setAccessToken(AccessTokenEntityInterface $accessToken): void
    {
        \assert($accessToken instanceof OAuthAccessToken);
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): AccessTokenEntityInterface
    {
        return $this->accessToken;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function setRevoked(bool $revoked): void
    {
        $this->revoked = $revoked;
    }
}
