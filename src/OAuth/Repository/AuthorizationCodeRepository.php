<?php

declare(strict_types=1);

namespace App\OAuth\Repository;

use App\Entity\OAuthAuthorizationCode;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

final readonly class AuthorizationCodeRepository implements AuthCodeRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new OAuthAuthorizationCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $this->entityManager->persist($authCodeEntity);
        $this->entityManager->flush();
    }

    public function revokeAuthCode(string $codeId): void
    {
        $authCode = $this->entityManager->getRepository(OAuthAuthorizationCode::class)->find($codeId);

        if ($authCode !== null) {
            $authCode->setRevoked(true);
            $this->entityManager->flush();
        }
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        $authCode = $this->entityManager->getRepository(OAuthAuthorizationCode::class)->find($codeId);

        return $authCode === null || $authCode->isRevoked();
    }
}
