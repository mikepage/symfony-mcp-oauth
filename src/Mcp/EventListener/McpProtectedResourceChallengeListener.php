<?php

declare(strict_types=1);

namespace App\Mcp\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds the RFC 9728 `resource_metadata` pointer to the `WWW-Authenticate` header
 * on 401 responses from `/mcp`, so an MCP client knows where to discover the
 * authorization server and obtain a token.
 *
 * This is a response listener rather than a firewall entry point on purpose: the
 * stock `access_token` authenticator emits its *own* 401 for a present-but-
 * invalid token via onAuthenticationFailure(), bypassing the entry point — which
 * only fires for the no-credentials path. A client whose token expired must
 * still get the discovery pointer to re-authenticate (RFC 9728 §5.1), so we
 * enrich the response after the fact, covering both 401 paths uniformly.
 */
#[AsEventListener(event: KernelEvents::RESPONSE)]
final readonly class McpProtectedResourceChallengeListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (Response::HTTP_UNAUTHORIZED !== $response->getStatusCode()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if ('/mcp' !== $path && !str_starts_with($path, '/mcp/')) {
            return;
        }

        $existing = $response->headers->get('WWW-Authenticate');

        if ($existing !== null && str_contains($existing, 'resource_metadata=')) {
            return;
        }

        $metadataUrl = $event->getRequest()->getSchemeAndHttpHost().'/.well-known/oauth-protected-resource';

        $response->headers->set('WWW-Authenticate', $existing !== null
            ? $existing.', resource_metadata="'.$metadataUrl.'"'
            : \sprintf('Bearer resource_metadata="%s"', $metadataUrl));
    }
}
