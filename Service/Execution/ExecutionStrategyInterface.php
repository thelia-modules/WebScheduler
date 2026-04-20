<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Service\Capability\CapabilityReport;

#[AutoconfigureTag('webscheduler.execution_strategy')]
interface ExecutionStrategyInterface
{
    public function name(): ExecutionStrategyEnum;

    public function priority(): int;

    public function supports(CapabilityReport $report): bool;

    public function execute(ExecutionContext $context): ExecutionResult;
}
