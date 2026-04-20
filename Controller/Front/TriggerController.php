<?php

declare(strict_types=1);

namespace WebScheduler\Controller\Front;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Front\BaseFrontController;
use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Service\Security\TriggerAuthenticator;
use WebScheduler\Service\Task\ExecutionRecorder;
use WebScheduler\Service\Task\TaskRunner;

class TriggerController extends BaseFrontController
{
    public function run(
        string $slug,
        Request $request,
        TriggerAuthenticator $authenticator,
        TaskRunner $runner,
        ExecutionRecorder $recorder,
    ): Response {
        $authentication = $authenticator->authenticate($slug, $request);

        if (!$authentication->isGranted()) {
            if (null !== $authentication->task) {
                $recorder->reject(
                    task: $authentication->task,
                    status: $authentication->rejectionStatus ?? ExecutionStatusEnum::UNAUTHORIZED,
                    reason: $authentication->reason,
                    triggerIp: $request->getClientIp(),
                );
            }

            return new JsonResponse(['accepted' => false], Response::HTTP_ACCEPTED);
        }

        $outcome = $runner->run($authentication->task, $request->getClientIp());

        return new JsonResponse(
            [
                'accepted' => true,
                'detached' => $outcome->detached,
                'strategy' => $outcome->strategyUsed->value,
                'status' => $outcome->status->value,
            ],
            Response::HTTP_ACCEPTED,
        );
    }
}
