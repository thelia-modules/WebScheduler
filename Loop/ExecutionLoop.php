<?php

declare(strict_types=1);

namespace WebScheduler\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use WebScheduler\Model\WebSchedulerExecutionQuery;

class ExecutionLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('task_id'),
            Argument::createIntTypeArgument('limit', 20),
        );
    }

    public function buildModelCriteria(): WebSchedulerExecutionQuery
    {
        $query = WebSchedulerExecutionQuery::create()->orderByTriggeredAt(Criteria::DESC);

        if (null !== $taskId = $this->getArgValue('task_id')) {
            $query->filterByTaskId($taskId);
        }

        $query->limit((int) $this->getArgValue('limit'));

        return $query;
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var \WebScheduler\Model\WebSchedulerExecution $execution */
        foreach ($loopResult->getResultDataCollection() as $execution) {
            $row = new LoopResultRow($execution);
            $row
                ->set('ID', $execution->getId())
                ->set('TASK_ID', $execution->getTaskId())
                ->set('TRIGGERED_AT', $execution->getTriggeredAt())
                ->set('STARTED_AT', $execution->getStartedAt())
                ->set('FINISHED_AT', $execution->getFinishedAt())
                ->set('STATUS', $execution->getStatus())
                ->set('EXIT_CODE', $execution->getExitCode())
                ->set('STRATEGY_USED', $execution->getStrategyUsed())
                ->set('OUTPUT', $execution->getOutput())
                ->set('TRIGGER_IP', $execution->getTriggerIp());

            $loopResult->addRow($row);
        }

        return $loopResult;
    }
}
