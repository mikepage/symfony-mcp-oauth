<?php

declare(strict_types=1);

namespace App\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;

/**
 * A trivial example tool that takes an argument and returns a result, to show
 * how tool inputs/outputs are wired. Replace with your own tools.
 */
final readonly class EchoTool
{
    /**
     * @return array{echo: string, length: int}
     */
    #[McpTool(name: 'echo', description: 'Echo a message back, with its character length.')]
    public function echo(string $message): array
    {
        return [
            'echo' => $message,
            'length' => mb_strlen($message),
        ];
    }
}
