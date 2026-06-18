<?php

declare(strict_types=1);

namespace App\OAuth;

use App\OAuth\Repository\AccessTokenRepository;
use App\OAuth\Repository\AuthorizationCodeRepository;
use App\OAuth\Repository\ClientRepository;
use App\OAuth\Repository\RefreshTokenRepository;
use App\OAuth\Repository\ScopeRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;

/**
 * Builds the league/oauth2-server authorization and resource servers, signing
 * access-token JWTs with the configured RSA key pair (see config/services.yaml
 * and `make keys`).
 */
final readonly class OAuthServerFactory
{
    private const string AUTH_CODE_TTL = 'PT10M';
    private const string ACCESS_TOKEN_TTL = 'PT1H';
    private const string REFRESH_TOKEN_TTL = 'P1M';

    public function __construct(
        private ClientRepository $clientRepository,
        private AccessTokenRepository $accessTokenRepository,
        private AuthorizationCodeRepository $authorizationCodeRepository,
        private RefreshTokenRepository $refreshTokenRepository,
        private ScopeRepository $scopeRepository,
        private string $privateKeyPath,
        private string $publicKeyPath,
        private ?string $passphrase,
        private string $encryptionKey,
    ) {
    }

    public function createAuthorizationServer(): AuthorizationServer
    {
        $privateKey = new CryptKey($this->privateKeyPath, $this->passphrase !== '' ? $this->passphrase : null, false);

        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $privateKey,
            $this->encryptionKey,
        );

        $server->setDefaultScope(ScopeRepository::SCOPE_MCP);

        // Authorization code grant with PKCE (required by default for public clients).
        $authCodeGrant = new AuthCodeGrant(
            $this->authorizationCodeRepository,
            $this->refreshTokenRepository,
            new \DateInterval(self::AUTH_CODE_TTL),
        );
        $authCodeGrant->setRefreshTokenTTL(new \DateInterval(self::REFRESH_TOKEN_TTL));
        $server->enableGrantType($authCodeGrant, new \DateInterval(self::ACCESS_TOKEN_TTL));

        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL(new \DateInterval(self::REFRESH_TOKEN_TTL));
        $server->enableGrantType($refreshTokenGrant, new \DateInterval(self::ACCESS_TOKEN_TTL));

        return $server;
    }

    public function createResourceServer(): ResourceServer
    {
        $publicKey = new CryptKey($this->publicKeyPath, null, false);

        return new ResourceServer($this->accessTokenRepository, $publicKey);
    }
}
