<?php

declare(strict_types=1);

namespace WebScheduler\Service\Task;

use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Enum\ExecutionStrategyEnum;

final readonly class TaskRunOutcome
{
    public function __construct(
        public ExecutionStatusEnum $status,
        public ExecutionStrategyEnum $strategyUsed,
        public bool $detached,
    ) {
    }
}
