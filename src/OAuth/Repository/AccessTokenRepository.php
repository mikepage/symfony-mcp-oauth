<?php

declare(strict_types=1);

namespace App\OAuth\Repository;

use App\Entity\OAuthAccessToken;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

final readonly class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null,
    ): AccessTokenEntityInterface {
        \assert($clientEntity instanceof \App\Entity\OAuthClient);

        $accessToken = new OAuthAccessToken();
        $accessToken->setClient($clientEntity);

        if ($userIdentifier !== null) {
            $accessToken->setUserIdentifier($userIdentifier);
        }

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->entityManager->persist($accessTokenEntity);
        $this->entityManager->flush();
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $accessToken = $this->entityManager->getRepository(OAuthAccessToken::class)->find($tokenId);

        if ($accessToken !== null) {
            $accessToken->setRevoked(true);
            $this->entityManager->flush();
        }
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $accessToken = $this->entityManager->getRepository(OAuthAccessToken::class)->find($tokenId);

        // An unknown token id is treated as revoked.
        return $accessToken === null || $accessToken->isRevoked();
    }
}
