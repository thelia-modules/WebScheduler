<?php

declare(strict_types=1);

namespace WebScheduler\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use WebScheduler\Model\WebSchedulerExecutionQuery;
use WebScheduler\WebScheduler;

class ExecutionController extends BaseAdminController
{
    private const PAGE_SIZE = 50;

    public function listAction(Request $request): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, TaskController::MODULE_CODE, AccessManager::VIEW)) {
            return $response;
        }

        $page = max(1, $request->query->getInt('page', 1));
        $taskId = $request->query->getInt('task_id', 0);

        $query = WebSchedulerExecutionQuery::create()->orderByTriggeredAt('DESC');

        if ($taskId > 0) {
            $query->filterByTaskId($taskId);
        }

        $executions = $query
            ->joinWithWebSchedulerTask()
            ->paginate($page, self::PAGE_SIZE);

        return $this->render('webscheduler/execution-list', [
            'executions' => $executions,
            'page' => $page,
            'task_id' => $taskId,
        ]);
    }

    public function detailAction(int $id): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, TaskController::MODULE_CODE, AccessManager::VIEW)) {
            return $response;
        }

        $execution = WebSchedulerExecutionQuery::create()
            ->joinWithWebSchedulerTask()
            ->findPk($id);

        if (null === $execution) {
            return $this->errorPage($this->getTranslator()->trans('Execution not found.', [], WebScheduler::DOMAIN_NAME), 404);
        }

        return $this->render('webscheduler/execution-detail', [
            'execution' => $execution,
        ]);
    }
}
