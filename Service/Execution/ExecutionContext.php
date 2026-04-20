<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

use WebScheduler\Model\WebSchedulerTask;

final readonly class ExecutionContext
{
    public function __construct(
        public WebSchedulerTask $task,
        public int $executionId,
        public \DateTimeImmutable $triggeredAt,
        public ?string $triggerIp,
    ) {
    }
}
