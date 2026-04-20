<?php

declare(strict_types=1);

namespace WebScheduler\Enum;

enum ExecutionStrategyEnum: string
{
    case AUTO = 'auto';
    case CLI_FORK = 'cli_fork';
    case FASTCGI_FINISH = 'fastcgi_finish';
    case SYNCHRONOUS = 'sync';

    public function label(): string
    {
        return match ($this) {
            self::AUTO => 'Auto',
            self::CLI_FORK => 'CLI fork',
            self::FASTCGI_FINISH => 'FastCGI finish',
            self::SYNCHRONOUS => 'Synchronous',
        };
    }

    public function isConcrete(): bool
    {
        return self::AUTO !== $this;
    }
}
