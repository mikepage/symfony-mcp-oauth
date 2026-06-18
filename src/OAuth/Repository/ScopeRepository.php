<?php

declare(strict_types=1);

namespace App\OAuth\Repository;

use App\OAuth\Entity\Scope;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

final class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * The single scope an MCP agent needs to call this server's tools. Add your
     * own scopes here (and grant them on the consent screen) as needed.
     */
    public const string SCOPE_MCP = 'mcp';

    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        return $identifier === self::SCOPE_MCP ? new Scope($identifier) : null;
    }

    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        // Drop anything we don't recognise, then always grant the MCP scope so a
        // token can reach the server's tools.
        $finalized = array_filter(
            $scopes,
            static fn (ScopeEntityInterface $scope): bool => $scope->getIdentifier() === self::SCOPE_MCP,
        );

        if ($finalized === []) {
            $finalized[] = new Scope(self::SCOPE_MCP);
        }

        return array_values($finalized);
    }
}
