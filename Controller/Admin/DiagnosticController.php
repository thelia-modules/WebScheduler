<?php

declare(strict_types=1);

namespace WebScheduler\Controller\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use WebScheduler\Enum\CapabilityEnum;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Service\Capability\CapabilityRepository;
use WebScheduler\Service\Execution\StrategyResolver;
use WebScheduler\WebScheduler;

class DiagnosticController extends BaseAdminController
{
    #[Route('/admin/module/WebScheduler/diagnostic', name: 'webscheduler.admin.diagnostic')]
    public function indexAction(CapabilityRepository $repository, StrategyResolver $resolver): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, TaskController::MODULE_CODE, AccessManager::VIEW)) {
            return $response;
        }

        $report = $repository->get();

        $capabilities = [];
        foreach (CapabilityEnum::cases() as $case) {
            $capabilities[] = [
                'key' => $case->value,
                'available' => $report->isAvailable($case),
                'details' => $report->details($case),
            ];
        }

        return $this->render('webscheduler/diagnostic', [
            'capabilities' => $capabilities,
            'strategies' => $this->presentStrategies($resolver->report($report)),
            'checked_at' => $report->checkedAt->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/admin/module/WebScheduler/diagnostic/refresh', name: 'webscheduler.admin.diagnostic.refresh', methods: ['POST'])]
    public function refreshAction(CapabilityRepository $repository, UrlGeneratorInterface $urlGenerator): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, TaskController::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $repository->refresh();

        return new RedirectResponse($urlGenerator->generate('webscheduler.admin.diagnostic'));
    }

    /**
     * The resolver returns positional tuples ordered by descending priority; the first
     * supported one is the strategy a task set to "auto" elects. Flatten that into named
     * keys so the template carries neither index arithmetic nor the running elected flag.
     *
     * @param list<array{0: ExecutionStrategyEnum, 1: bool, 2: int}> $report
     *
     * @return list<array{key: string, label: string, supported: bool, priority: int, elected: bool}>
     */
    private function presentStrategies(array $report): array
    {
        $translator = $this->getTranslator();
        $electedFound = false;
        $rows = [];

        foreach ($report as [$strategy, $supported, $priority]) {
            $elected = $supported && !$electedFound;
            $electedFound = $electedFound || $elected;

            $rows[] = [
                'key' => $strategy->value,
                'label' => $translator->trans($strategy->label(), [], WebScheduler::DOMAIN_NAME),
                'supported' => $supported,
                'priority' => $priority,
                'elected' => $elected,
            ];
        }

        return $rows;
    }
}
