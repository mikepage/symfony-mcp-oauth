<?php

declare(strict_types=1);

namespace App\OAuth\Security;

use App\OAuth\Psr7Bridge;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resource-server token handler for the stateless `mcp` firewall. Validates the
 * OAuth bearer token with league's {@see ResourceServer} (JWT signature, expiry
 * and revocation) and loads the user the token was issued for; the token `sub`
 * is the user's email, matching the `app_user_provider`.
 */
final readonly class OAuthAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private ResourceServer $resourceServer,
        private Psr7Bridge $psr7Bridge,
        private LoggerInterface $logger,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $psrRequest = $this->psr7Bridge->createBearerServerRequest($accessToken);

        try {
            $psrRequest = $this->resourceServer->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException $exception) {
            // league rejected the bearer token: bad/expired JWT signature, or
            // isAccessTokenRevoked() returned true (an unknown jti is treated as
            // revoked). This is the most common cause of a freshly-issued token
            // being refused on reconnect.
            $this->logger->warning('[MCP] bearer token rejected by resource server', [
                'error_type' => $exception->getErrorType(),
                'message' => $exception->getMessage(),
                'hint' => $exception->getHint(),
            ]);

            throw new BadCredentialsException($exception->getMessage(), previous: $exception);
        }

        $userIdentifier = $psrRequest->getAttribute('oauth_user_id');
        $tokenId = $psrRequest->getAttribute('oauth_access_token_id');
        $clientId = $psrRequest->getAttribute('oauth_client_id');
        $scopes = $psrRequest->getAttribute('oauth_scopes');

        if (!\is_string($userIdentifier) || $userIdentifier === '') {
            $this->logger->warning('[MCP] access token is not bound to a user', [
                'token_id' => $tokenId,
                'client_id' => $clientId,
            ]);

            throw new BadCredentialsException('Access token is not bound to a user.');
        }

        $this->logger->info('[MCP] bearer token validated', [
            'user' => $userIdentifier,
            'client_id' => $clientId,
            'token_id' => $tokenId,
            'scopes' => $scopes,
        ]);

        return new UserBadge($userIdentifier);
    }
}
