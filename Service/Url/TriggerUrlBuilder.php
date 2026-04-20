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

    public function build(WebSchedulerTask $task, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $signature = $this->signer->sign($task->getSlug(), $task->getSecret(), $timestamp);

        $path = sprintf(
            '/web-scheduler/run/%s?ts=%d&sig=%s',
            $task->getSlug(),
            $timestamp,
            $signature,
        );

        return URL::getInstance()?->absoluteUrl($path) ?? $path;
    }
}
