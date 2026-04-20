<?php

declare(strict_types=1);

namespace WebScheduler\Service\Command;

use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Input\StringInput;
use WebScheduler\Service\Execution\CommandArgumentTokenizer;

final readonly class CommandValidator
{
    public function __construct(
        private CommandRegistry $registry,
        private CommandArgumentTokenizer $tokenizer,
    ) {
    }

    public function validate(string $commandName, ?string $arguments): void
    {
        if (!$this->registry->exists($commandName)) {
            throw new \InvalidArgumentException(sprintf('Unknown command "%s".', $commandName));
        }

        try {
            new StringInput((string) $arguments);
        } catch (ConsoleRuntimeException $e) {
            throw new \InvalidArgumentException('Invalid command arguments: '.$e->getMessage(), previous: $e);
        }

        $tokens = $this->tokenizer->tokenize($arguments);

        foreach ($tokens as $token) {
            if (str_contains($token, "\n") || str_contains($token, "\0")) {
                throw new \InvalidArgumentException('Command arguments must not contain control characters.');
            }
        }
    }
}
