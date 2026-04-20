<?php

declare(strict_types=1);

namespace WebScheduler\Controller\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Tools\URL;
use WebScheduler\Enum\CapabilityEnum;
use WebScheduler\Service\Capability\CapabilityRepository;
use WebScheduler\Service\Execution\StrategyResolver;

class DiagnosticController extends BaseAdminController
{
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
            'strategies' => $resolver->report($report),
            'checked_at' => $report->checkedAt,
        ]);
    }

    public function refreshAction(CapabilityRepository $repository): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, TaskController::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $repository->refresh();

        return new RedirectResponse(URL::getInstance()?->absoluteUrl('/admin/module/WebScheduler/diagnostic') ?? '/admin/module/WebScheduler');
    }
}
