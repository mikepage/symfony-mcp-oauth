<?php

declare(strict_types=1);

namespace App\OAuth\Repository;

use App\Entity\OAuthClient;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

final readonly class ClientRepository implements ClientRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        return $this->entityManager->getRepository(OAuthClient::class)->find($clientIdentifier);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $client = $this->getClientEntity($clientIdentifier);

        if (!$client instanceof OAuthClient) {
            return false;
        }

        if ($grantType !== null && !$client->supportsGrantType($grantType)) {
            return false;
        }

        // Public clients (e.g. MCP clients using PKCE) authenticate without a secret.
        if (!$client->isConfidential()) {
            return true;
        }

        if ($clientSecret === null || $client->getSecret() === null) {
            return false;
        }

        return password_verify($clientSecret, $client->getSecret());
    }
}
