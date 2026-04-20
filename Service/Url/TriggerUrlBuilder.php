<?php

declare(strict_types=1);

namespace WebScheduler\Service\Url;

use Thelia\Tools\URL;
use WebScheduler\Model\WebSchedulerTask;
use WebScheduler\Service\Security\HmacSigner;

final readonly class TriggerUrlBuilder
{
    public function __construct(
        private HmacSigner $signer,
    ) {
    }

    public function build(WebSchedulerTask $task): string
    {
        $path = sprintf(
            '/web-scheduler/run/%s?sig=%s',
            $task->getSlug(),
            $this->signer->sign($task->getSlug(), $task->getSecret()),
        );

        return URL::getInstance()?->absoluteUrl($path) ?? $path;
    }
}
