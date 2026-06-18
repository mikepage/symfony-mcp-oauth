<?php

declare(strict_types=1);

namespace App\Mcp\Tool;

use App\Entity\User;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * A minimal MCP tool that proves the OAuth handshake end to end: it reports the
 * user the calling agent's access token was issued for.
 */
final readonly class WhoamiTool
{
    public function __construct(private Security $security)
    {
    }

    /**
     * @return array{authenticated: bool, email?: string, name?: string, roles?: string[]}
     */
    #[McpTool(name: 'whoami', description: 'Return the authenticated user the MCP access token belongs to.')]
    public function whoami(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return ['authenticated' => false];
        }

        return [
            'authenticated' => true,
            'email' => $user->getUserIdentifier(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
        ];
    }
}
