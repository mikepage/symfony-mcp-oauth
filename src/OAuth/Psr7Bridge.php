<?php

declare(strict_types=1);

namespace App\OAuth;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts between Symfony's HttpFoundation and PSR-7, which is the interface
 * league/oauth2-server speaks.
 */
final class Psr7Bridge
{
    private readonly Psr17Factory $psr17Factory;
    private readonly PsrHttpFactory $psrHttpFactory;
    private readonly HttpFoundationFactory $httpFoundationFactory;

    public function __construct()
    {
        $this->psr17Factory = new Psr17Factory();
        $this->psrHttpFactory = new PsrHttpFactory($this->psr17Factory, $this->psr17Factory, $this->psr17Factory, $this->psr17Factory);
        $this->httpFoundationFactory = new HttpFoundationFactory();
    }

    public function toPsrRequest(Request $request): ServerRequestInterface
    {
        return $this->psrHttpFactory->createRequest($request);
    }

    /**
     * Build a minimal PSR-7 request carrying just the bearer token, so league's
     * ResourceServer can validate it. The stock Symfony `access_token`
     * authenticator hands the token handler only the raw token string, not the
     * original request, so we synthesise one here.
     */
    public function createBearerServerRequest(string $accessToken): ServerRequestInterface
    {
        return $this->psr17Factory
            ->createServerRequest('GET', '/mcp')
            ->withHeader('Authorization', 'Bearer '.$accessToken)
        ;
    }

    public function createResponse(): ResponseInterface
    {
        return $this->psr17Factory->createResponse();
    }

    public function toSymfonyResponse(ResponseInterface $response): Response
    {
        return $this->httpFoundationFactory->createResponse($response);
    }
}
