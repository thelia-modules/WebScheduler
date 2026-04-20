<?php

declare(strict_types=1);

namespace WebScheduler\Enum;

enum ExecutionStatusEnum: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case TIMEOUT = 'timeout';
    case SKIPPED_LOCKED = 'skipped_locked';
    case UNAUTHORIZED = 'unauthorized';
    case RATE_LIMITED = 'rate_limited';
    case IP_DENIED = 'ip_denied';
    case DISABLED = 'disabled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::PENDING, self::RUNNING => false,
            default => true,
        };
    }

    public function isFailure(): bool
    {
        return match ($this) {
            self::FAILED, self::TIMEOUT, self::UNAUTHORIZED, self::IP_DENIED => true,
            default => false,
        };
    }
}
