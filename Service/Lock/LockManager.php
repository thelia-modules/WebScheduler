<?php

declare(strict_types=1);

namespace WebScheduler\Service\Lock;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use WebScheduler\Model\WebSchedulerTask;

final class LockManager
{
    private ?LockFactory $factory = null;

    public function acquire(WebSchedulerTask $task): ?LockInterface
    {
        $lock = $this->factory()->createLock('web_scheduler_task_'.$task->getId());

        return $lock->acquire() ? $lock : null;
    }

    private function factory(): LockFactory
    {
        return $this->factory ??= new LockFactory(new FlockStore($this->lockDirectory()));
    }

    private function lockDirectory(): string
    {
        $dir = \THELIA_ROOT.'var'.\DIRECTORY_SEPARATOR.'webscheduler'.\DIRECTORY_SEPARATOR.'locks';

        if (is_dir($dir) || @mkdir($dir, 0775, true) || is_dir($dir)) {
            return $dir;
        }

        return sys_get_temp_dir();
    }
}
