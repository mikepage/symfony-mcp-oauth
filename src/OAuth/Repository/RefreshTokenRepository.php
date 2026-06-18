<?php

declare(strict_types=1);

namespace App\OAuth\Repository;

use App\Entity\OAuthRefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

final readonly class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getNewRefreshToken(): RefreshTokenEntityInterface
    {
        return new OAuthRefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $this->entityManager->persist($refreshTokenEntity);
        $this->entityManager->flush();
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $refreshToken = $this->entityManager->getRepository(OAuthRefreshToken::class)->find($tokenId);

        if ($refreshToken !== null) {
            $refreshToken->setRevoked(true);
            $this->entityManager->flush();
        }
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $refreshToken = $this->entityManager->getRepository(OAuthRefreshToken::class)->find($tokenId);

        return $refreshToken === null || $refreshToken->isRevoked();
    }
}
