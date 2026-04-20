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
use Thelia\Type\BooleanOrBothType;
use Thelia\Type\EnumType;
use Thelia\Type\TypeCollection;
use WebScheduler\Model\WebSchedulerTaskQuery;

class TaskLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('id'),
            Argument::createAnyTypeArgument('slug'),
            new Argument(
                'enabled',
                new TypeCollection(new BooleanOrBothType()),
                '*',
            ),
            new Argument(
                'order',
                new TypeCollection(new EnumType(['title', 'title_reverse', 'last_triggered', 'last_triggered_reverse'])),
                'title',
            ),
        );
    }

    public function buildModelCriteria(): WebSchedulerTaskQuery
    {
        $query = WebSchedulerTaskQuery::create();

        if (null !== $id = $this->getArgValue('id')) {
            $query->filterById($id);
        }

        if (null !== $slug = $this->getArgValue('slug')) {
            $query->filterBySlug($slug);
        }

        $enabled = $this->getArgValue('enabled');
        if (\is_bool($enabled)) {
            $query->filterByEnabled($enabled);
        }

        match ($this->getArgValue('order')) {
            'title' => $query->orderByTitle(Criteria::ASC),
            'title_reverse' => $query->orderByTitle(Criteria::DESC),
            'last_triggered' => $query->orderByLastTriggeredAt(Criteria::ASC),
            'last_triggered_reverse' => $query->orderByLastTriggeredAt(Criteria::DESC),
            default => $query->orderByTitle(Criteria::ASC),
        };

        return $query;
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var \WebScheduler\Model\WebSchedulerTask $task */
        foreach ($loopResult->getResultDataCollection() as $task) {
            $row = new LoopResultRow($task);
            $row
                ->set('ID', $task->getId())
                ->set('SLUG', $task->getSlug())
                ->set('TITLE', $task->getTitle())
                ->set('COMMAND_NAME', $task->getCommandName())
                ->set('COMMAND_ARGUMENTS', $task->getCommandArguments())
                ->set('STRATEGY', $task->getStrategy())
                ->set('ENABLED', $task->getEnabled())
                ->set('LAST_TRIGGERED_AT', $task->getLastTriggeredAt());

            $loopResult->addRow($row);
        }

        return $loopResult;
    }
}
