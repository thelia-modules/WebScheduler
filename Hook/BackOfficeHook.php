<?php

declare(strict_types=1);

namespace WebScheduler\Hook;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
use WebScheduler\WebScheduler;

class BackOfficeHook extends BaseHook
{
    /**
     * Dependencies come through the constructor and the two parser arguments are relayed to
     * the parent: a `#[Required]` property would stay unwired under autowiring, `render()`
     * would throw and hook isolation would swallow it into a blank response.
     */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public function onToolsMenu(HookRenderBlockEvent $event): void
    {
        $event->add([
            'id' => 'tools_menu_web_scheduler',
            'class' => '',
            'url' => $this->urlGenerator->generate('webscheduler.admin.task.list'),
            'title' => $this->trans('Web Scheduler', [], WebScheduler::DOMAIN_NAME),
        ]);
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $event->add($this->render('webscheduler/module-configuration.html.twig', [
            'tasks_url' => $this->urlGenerator->generate('webscheduler.admin.task.list'),
            'executions_url' => $this->urlGenerator->generate('webscheduler.admin.execution.list'),
            'diagnostic_url' => $this->urlGenerator->generate('webscheduler.admin.diagnostic'),
        ]));
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'main.top-menu-tools' => [
                'type' => 'back',
                'method' => 'onToolsMenu',
            ],
            'module.configuration' => [
                'type' => 'back',
                'method' => 'onModuleConfiguration',
            ],
        ];
    }
}
