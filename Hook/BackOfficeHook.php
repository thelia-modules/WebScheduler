<?php

declare(strict_types=1);

namespace WebScheduler\Hook;

use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Tools\URL;
use WebScheduler\WebScheduler;

class BackOfficeHook extends BaseHook
{
    public function onToolsMenu(HookRenderBlockEvent $event): void
    {
        $event->add([
            'id' => 'tools_menu_web_scheduler',
            'class' => '',
            'url' => URL::getInstance()?->absoluteUrl('/admin/module/WebScheduler') ?? '#',
            'title' => $this->trans('Web Scheduler', [], WebScheduler::DOMAIN_NAME),
        ]);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'main.top-menu-tools' => [
                'type' => 'back',
                'method' => 'onToolsMenu',
            ],
        ];
    }
}
