<?php

declare(strict_types=1);

namespace WebScheduler\Service\Security;

use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Model\WebSchedulerTask;

final readonly class AuthenticationResult
{
    private function __construct(
        public ?WebSchedulerTask $task,
        public ?ExecutionStatusEnum $rejectionStatus,
        public string $reason,
    ) {
    }

    public static function granted(WebSchedulerTask $task): self
    {
        return new self($task, null, '');
    }

    public static function denied(ExecutionStatusEnum $status, string $reason): self
    {
        return new self(null, $status, $reason);
    }

    public function isGranted(): bool
    {
        return null !== $this->task;
    }
}
