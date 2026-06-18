<?php

declare(strict_types=1);

namespace App\OAuth\Controller;

use App\OAuth\Repository\ScopeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * OAuth discovery documents the MCP client crawls before authenticating:
 * authorization-server metadata (RFC 8414) and protected-resource metadata
 * (RFC 9728).
 */
final class MetadataController
{
    #[Route('/.well-known/oauth-authorization-server', name: 'oauth_metadata_authorization_server', methods: ['GET'])]
    #[Route('/.well-known/openid-configuration', name: 'oauth_metadata_openid_configuration', methods: ['GET'])]
    public function authorizationServer(Request $request): JsonResponse
    {
        $base = $request->getSchemeAndHttpHost();

        return new JsonResponse([
            'issuer' => $base,
            'authorization_endpoint' => $base.'/oauth/authorize',
            'token_endpoint' => $base.'/oauth/token',
            'registration_endpoint' => $base.'/oauth/register',
            'scopes_supported' => [ScopeRepository::SCOPE_MCP],
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256'],
        ]);
    }

    #[Route('/.well-known/oauth-protected-resource', name: 'oauth_metadata_protected_resource', methods: ['GET'])]
    #[Route('/.well-known/oauth-protected-resource/mcp', name: 'oauth_metadata_protected_resource_mcp', methods: ['GET'])]
    public function protectedResource(Request $request): JsonResponse
    {
        $base = $request->getSchemeAndHttpHost();

        return new JsonResponse([
            'resource' => $base.'/mcp',
            'authorization_servers' => [$base],
            'scopes_supported' => [ScopeRepository::SCOPE_MCP],
            'bearer_methods_supported' => ['header'],
        ]);
    }
}
