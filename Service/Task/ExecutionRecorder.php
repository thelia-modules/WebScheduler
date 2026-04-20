<?php

declare(strict_types=1);

namespace WebScheduler\Service\Task;

use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Model\WebSchedulerExecution;
use WebScheduler\Model\WebSchedulerTask;

final readonly class ExecutionRecorder
{
    public function open(WebSchedulerTask $task, ?string $triggerIp): WebSchedulerExecution
    {
        $execution = new WebSchedulerExecution();
        $execution
            ->setTaskId($task->getId())
            ->setTriggeredAt(new \DateTimeImmutable())
            ->setStatus(ExecutionStatusEnum::PENDING->value)
            ->setTriggerIp($triggerIp)
            ->save();

        return $execution;
    }

    public function markStarted(WebSchedulerExecution $execution, ExecutionStrategyEnum $strategy): void
    {
        $execution
            ->setStartedAt(new \DateTimeImmutable())
            ->setStrategyUsed($strategy->value)
            ->setStatus(ExecutionStatusEnum::RUNNING->value)
            ->save();
    }

    public function finalize(
        WebSchedulerExecution $execution,
        ExecutionStatusEnum $status,
        ExecutionStrategyEnum $strategy,
        ?int $exitCode,
        string $output,
    ): void {
        $execution
            ->setFinishedAt(new \DateTimeImmutable())
            ->setStatus($status->value)
            ->setStrategyUsed($strategy->value)
            ->setExitCode($exitCode)
            ->setOutput($output)
            ->save();
    }

    public function reject(
        WebSchedulerTask $task,
        ExecutionStatusEnum $status,
        string $reason,
        ?string $triggerIp,
    ): WebSchedulerExecution {
        $now = new \DateTimeImmutable();
        $execution = new WebSchedulerExecution();
        $execution
            ->setTaskId($task->getId())
            ->setTriggeredAt($now)
            ->setFinishedAt($now)
            ->setStatus($status->value)
            ->setOutput($reason)
            ->setTriggerIp($triggerIp)
            ->save();

        return $execution;
    }
}
