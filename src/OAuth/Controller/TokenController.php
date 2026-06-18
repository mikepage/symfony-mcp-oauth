<?php

declare(strict_types=1);

namespace App\OAuth\Controller;

use App\OAuth\Psr7Bridge;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The OAuth token endpoint. Exchanges an authorization code (with PKCE) or a
 * refresh token for an access token.
 */
final class TokenController
{
    public function __construct(
        private readonly AuthorizationServer $authorizationServer,
        private readonly Psr7Bridge $psr7Bridge,
    ) {
    }

    #[Route('/oauth/token', name: 'oauth_token', methods: ['POST'])]
    public function token(Request $request): Response
    {
        $psrRequest = $this->psr7Bridge->toPsrRequest($request);
        $psrResponse = $this->psr7Bridge->createResponse();

        try {
            $psrResponse = $this->authorizationServer->respondToAccessTokenRequest($psrRequest, $psrResponse);
        } catch (OAuthServerException $exception) {
            $psrResponse = $exception->generateHttpResponse($psrResponse);
        }

        return $this->psr7Bridge->toSymfonyResponse($psrResponse);
    }
}
