<?php

declare(strict_types=1);

namespace App\Entity;

use App\OAuth\Entity\Scope;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * A short-lived authorization code exchanged for tokens at the token endpoint.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oauth_authorization_code')]
class OAuthAuthorizationCode implements AuthCodeEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 80)]
    private string $identifier;

    #[ORM\ManyToOne(targetEntity: OAuthClient::class)]
    #[ORM\JoinColumn(name: 'client_identifier', referencedColumnName: 'identifier', nullable: false, onDelete: 'CASCADE')]
    private OAuthClient $client;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userIdentifier = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON)]
    private array $scopes = [];

    #[ORM\Column]
    private \DateTimeImmutable $expiryDateTime;

    #[ORM\Column(length: 2000, nullable: true)]
    private ?string $redirectUri = null;

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

    public function setUserIdentifier(string $identifier): void
    {
        $this->userIdentifier = $identifier;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    public function setClient(ClientEntityInterface $client): void
    {
        \assert($client instanceof OAuthClient);
        $this->client = $client;
    }

    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->scopes[$scope->getIdentifier()] = $scope->getIdentifier();
    }

    /**
     * @return ScopeEntityInterface[]
     */
    public function getScopes(): array
    {
        return array_map(static fn (string $scope): Scope => new Scope($scope), array_values($this->scopes));
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $uri): void
    {
        $this->redirectUri = $uri;
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
