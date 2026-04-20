<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Service\Capability\CapabilityReport;
use WebScheduler\Service\Capability\CapabilityRepository;

final readonly class StrategyResolver
{
    /**
     * @param iterable<ExecutionStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator('webscheduler.execution_strategy')]
        private iterable $strategies,
        private CapabilityRepository $capabilityRepository,
    ) {
    }

    public function resolve(ExecutionStrategyEnum $preferred): ExecutionStrategyInterface
    {
        $report = $this->capabilityRepository->get();
        $ordered = $this->orderedByPriority();

        if (ExecutionStrategyEnum::AUTO !== $preferred) {
            foreach ($ordered as $strategy) {
                if ($strategy->name() === $preferred && $strategy->supports($report)) {
                    return $strategy;
                }
            }
        }

        foreach ($ordered as $strategy) {
            if ($strategy->supports($report)) {
                return $strategy;
            }
        }

        throw new \RuntimeException('No supported execution strategy available.');
    }

    /**
     * @return list<array{0: ExecutionStrategyEnum, 1: bool, 2: int}>
     */
    public function report(CapabilityReport $report): array
    {
        $lines = [];

        foreach ($this->orderedByPriority() as $strategy) {
            $lines[] = [$strategy->name(), $strategy->supports($report), $strategy->priority()];
        }

        return $lines;
    }

    /**
     * @return list<ExecutionStrategyInterface>
     */
    private function orderedByPriority(): array
    {
        $list = iterator_to_array($this->strategies, false);

        usort(
            $list,
            static fn (ExecutionStrategyInterface $a, ExecutionStrategyInterface $b): int => $b->priority() <=> $a->priority(),
        );

        return $list;
    }
}
