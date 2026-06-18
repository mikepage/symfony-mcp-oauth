<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * An OAuth 2.1 client. MCP clients register themselves dynamically (RFC 7591)
 * as public PKCE clients; confidential clients carry a hashed secret.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oauth_client')]
class OAuthClient implements ClientEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 80)]
    private string $identifier;

    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * Argon/bcrypt hash of the client secret, or null for public clients.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secret = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON)]
    private array $redirectUris = [];

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON)]
    private array $grants = [];

    #[ORM\Column]
    private bool $confidential = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string[]
     */
    public function getRedirectUri(): string|array
    {
        return $this->redirectUris;
    }

    /**
     * @param string[] $redirectUris
     */
    public function setRedirectUris(array $redirectUris): void
    {
        $this->redirectUris = array_values($redirectUris);
    }

    /**
     * @return string[]
     */
    public function getGrants(): array
    {
        return $this->grants;
    }

    /**
     * @param string[] $grants
     */
    public function setGrants(array $grants): void
    {
        $this->grants = array_values($grants);
    }

    public function supportsGrantType(string $grantType): bool
    {
        return \in_array($grantType, $this->grants, true);
    }

    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    public function setConfidential(bool $confidential): void
    {
        $this->confidential = $confidential;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
