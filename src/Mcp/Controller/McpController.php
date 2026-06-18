<?php

declare(strict_types=1);

namespace App\Mcp\Controller;

use Mcp\Server;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtocolVersionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces the bundle's McpController (aliased as `mcp.server.controller`) so we
 * can hand the Streamable HTTP transport an explicit middleware stack.
 *
 * The SDK default {@see DnsRebindingProtectionMiddleware} only allowlists
 * localhost, so it answers every request on a real host with a 403 "Invalid
 * Host header" — before the JSON-RPC message is ever processed — which clients
 * surface as "credentials rejected on reconnect". We keep the CORS and
 * protocol-version checks and allowlist the deployment hosts instead (see the
 * `mcp.allowed_hosts` parameter in config/services.yaml).
 */
final class McpController
{
    /**
     * @param list<string> $allowedHosts hostnames (without port) permitted by the DNS-rebinding check
     */
    public function __construct(
        private readonly Server $server,
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly array $allowedHosts,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function handle(Request $request): Response
    {
        $transport = new StreamableHttpTransport(
            $this->httpMessageFactory->createRequest($request),
            $this->responseFactory,
            $this->streamFactory,
            logger: $this->logger,
            middleware: [
                new CorsMiddleware(),
                new DnsRebindingProtectionMiddleware($this->allowedHosts, $this->responseFactory, $this->streamFactory),
                new ProtocolVersionMiddleware(),
            ],
        );

        $psrResponse = $this->server->run($transport);
        $streamed = 'text/event-stream' === strtolower($psrResponse->getHeaderLine('Content-Type'));

        return $this->httpFoundationFactory->createResponse($psrResponse, $streamed);
    }
}
