<?php

declare(strict_types=1);

namespace WebScheduler\Service\Task;

use Psr\Log\LoggerInterface;
use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Model\WebSchedulerTask;
use WebScheduler\Service\Execution\ExecutionContext;
use WebScheduler\Service\Execution\StrategyResolver;
use WebScheduler\Service\Lock\LockManager;

final readonly class TaskRunner
{
    public function __construct(
        private StrategyResolver $strategyResolver,
        private LockManager $lockManager,
        private ExecutionRecorder $recorder,
        private LoggerInterface $logger,
    ) {
    }

    public function run(WebSchedulerTask $task, ?string $triggerIp): TaskRunOutcome
    {
        $execution = $this->recorder->open($task, $triggerIp);

        $lock = $this->lockManager->acquire($task);

        if (null === $lock) {
            $this->recorder->finalize(
                $execution,
                ExecutionStatusEnum::SKIPPED_LOCKED,
                ExecutionStrategyEnum::AUTO,
                null,
                'Another execution is in progress for this task.',
            );

            return new TaskRunOutcome(ExecutionStatusEnum::SKIPPED_LOCKED, ExecutionStrategyEnum::AUTO, true);
        }

        $task->setLastTriggeredAt(new \DateTimeImmutable())->save();

        try {
            $preferred = ExecutionStrategyEnum::tryFrom($task->getStrategy()) ?? ExecutionStrategyEnum::AUTO;
            $strategy = $this->strategyResolver->resolve($preferred);

            $this->recorder->markStarted($execution, $strategy->name());

            $context = new ExecutionContext(
                task: $task,
                executionId: $execution->getId(),
                triggeredAt: new \DateTimeImmutable(),
                triggerIp: $triggerIp,
            );

            $result = $strategy->execute($context);

            $this->recorder->finalize(
                $execution,
                $result->status,
                $result->strategyUsed,
                $result->exitCode,
                $result->output,
            );

            return new TaskRunOutcome($result->status, $result->strategyUsed, $result->detached);
        } catch (\Throwable $e) {
            $this->logger->error('WebScheduler task failed', [
                'task_id' => $task->getId(),
                'exception' => $e,
            ]);

            $this->recorder->finalize(
                $execution,
                ExecutionStatusEnum::FAILED,
                ExecutionStrategyEnum::AUTO,
                null,
                $e::class.': '.$e->getMessage(),
            );

            return new TaskRunOutcome(ExecutionStatusEnum::FAILED, ExecutionStrategyEnum::AUTO, false);
        } finally {
            $lock->release();
        }
    }
}
