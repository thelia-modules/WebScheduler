<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution\Strategy;

use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Service\Capability\CapabilityReport;
use WebScheduler\Service\Execution\ExecutionContext;
use WebScheduler\Service\Execution\ExecutionResult;
use WebScheduler\Service\Execution\ExecutionStrategyInterface;
use WebScheduler\Service\Execution\InProcessCommandRunner;

final readonly class SynchronousStrategy implements ExecutionStrategyInterface
{
    public function __construct(
        private InProcessCommandRunner $inProcessRunner,
    ) {
    }

    public function name(): ExecutionStrategyEnum
    {
        return ExecutionStrategyEnum::SYNCHRONOUS;
    }

    public function priority(): int
    {
        return 10;
    }

    public function supports(CapabilityReport $report): bool
    {
        return true;
    }

    public function execute(ExecutionContext $context): ExecutionResult
    {
        $result = $this->inProcessRunner->run($context->task);

        return ExecutionResult::completed($this->name(), $result->exitCode, $result->output);
    }
}
