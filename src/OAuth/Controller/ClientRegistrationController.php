<?php

declare(strict_types=1);

namespace App\OAuth\Controller;

use App\Entity\OAuthClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * OAuth 2.0 Dynamic Client Registration (RFC 7591). MCP clients self-register
 * here before starting the authorization-code flow.
 */
final class ClientRegistrationController
{
    private const array SUPPORTED_AUTH_METHODS = ['none', 'client_secret_basic', 'client_secret_post'];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/oauth/register', name: 'oauth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->error('invalid_client_metadata', 'Request body must be valid JSON.');
        }

        $redirectUris = $payload['redirect_uris'] ?? null;

        if (!\is_array($redirectUris) || $redirectUris === []) {
            return $this->error('invalid_redirect_uri', 'At least one redirect URI is required.');
        }

        foreach ($redirectUris as $redirectUri) {
            if (!\is_string($redirectUri) || !$this->isValidRedirectUri($redirectUri)) {
                return $this->error('invalid_redirect_uri', \sprintf('Invalid redirect URI: %s', \is_string($redirectUri) ? $redirectUri : 'non-string value'));
            }
        }

        $authMethod = $payload['token_endpoint_auth_method'] ?? 'none';

        if (!\in_array($authMethod, self::SUPPORTED_AUTH_METHODS, true)) {
            return $this->error('invalid_client_metadata', \sprintf('Unsupported token_endpoint_auth_method: %s', \is_string($authMethod) ? $authMethod : 'non-string value'));
        }

        $isConfidential = 'none' !== $authMethod;

        $client = new OAuthClient();
        $client->setIdentifier(bin2hex(random_bytes(16)));
        $client->setName(\is_string($payload['client_name'] ?? null) ? $payload['client_name'] : 'MCP Client');
        $client->setRedirectUris(array_values($redirectUris));
        $client->setGrants(['authorization_code', 'refresh_token']);
        $client->setConfidential($isConfidential);

        $plainSecret = null;

        if ($isConfidential) {
            $plainSecret = bin2hex(random_bytes(32));
            $client->setSecret(password_hash($plainSecret, \PASSWORD_DEFAULT));
        }

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $response = [
            'client_id' => $client->getIdentifier(),
            'client_id_issued_at' => $client->getCreatedAt()->getTimestamp(),
            'redirect_uris' => $client->getRedirectUri(),
            'grant_types' => $client->getGrants(),
            'response_types' => ['code'],
            'token_endpoint_auth_method' => $authMethod,
            'client_name' => $client->getName(),
        ];

        if ($plainSecret !== null) {
            $response['client_secret'] = $plainSecret;
            $response['client_secret_expires_at'] = 0; // never expires
        }

        return new JsonResponse($response, Response::HTTP_CREATED);
    }

    private function isValidRedirectUri(string $uri): bool
    {
        $parts = parse_url($uri);

        // Require an absolute URI with a scheme; this permits https, http for
        // loopback, and custom/native-app schemes that MCP clients may use.
        return $parts !== false && isset($parts['scheme']) && $parts['scheme'] !== '';
    }

    private function error(string $error, string $description): JsonResponse
    {
        return new JsonResponse([
            'error' => $error,
            'error_description' => $description,
        ], Response::HTTP_BAD_REQUEST);
    }
}
