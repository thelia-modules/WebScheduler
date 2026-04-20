<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Enum\ExecutionStrategyEnum;

final readonly class ExecutionResult
{
    public function __construct(
        public ExecutionStatusEnum $status,
        public ExecutionStrategyEnum $strategyUsed,
        public ?int $exitCode,
        public string $output,
        public bool $detached,
    ) {
    }

    public static function detached(ExecutionStrategyEnum $strategy, string $output = ''): self
    {
        return new self(ExecutionStatusEnum::RUNNING, $strategy, null, $output, true);
    }

    public static function completed(
        ExecutionStrategyEnum $strategy,
        int $exitCode,
        string $output,
    ): self {
        $status = 0 === $exitCode ? ExecutionStatusEnum::SUCCESS : ExecutionStatusEnum::FAILED;

        return new self($status, $strategy, $exitCode, $output, false);
    }

    public static function timedOut(ExecutionStrategyEnum $strategy, string $output): self
    {
        return new self(ExecutionStatusEnum::TIMEOUT, $strategy, null, $output, false);
    }

    public static function failed(ExecutionStrategyEnum $strategy, string $output): self
    {
        return new self(ExecutionStatusEnum::FAILED, $strategy, null, $output, false);
    }
}
