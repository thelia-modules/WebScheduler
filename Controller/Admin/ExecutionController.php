<?php

declare(strict_types=1);

namespace WebScheduler\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use WebScheduler\Model\WebSchedulerExecution;
use WebScheduler\Model\WebSchedulerExecutionQuery;
use WebScheduler\WebScheduler;

class ExecutionController extends BaseAdminController
{
    private const PAGE_SIZE = 50;

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /** Bootstrap contextual class per execution status; anything else is an error. */
    private const STATUS_VARIANTS = [
        'success' => 'success',
        'running' => 'info',
        'skipped_locked' => 'warning',
        'rate_limited' => 'warning',
    ];

    #[Route('/admin/module/WebScheduler/executions', name: 'webscheduler.admin.execution.list')]
    public function listAction(Request $request, UrlGeneratorInterface $urlGenerator): Response
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

        $rows = [];
        foreach ($executions as $execution) {
            $rows[] = $this->presentRow($execution, $urlGenerator);
        }

        return $this->render('webscheduler/execution-list', [
            'rows' => $rows,
            'page' => $page,
            'task_id' => $taskId > 0 ? $taskId : null,
            'has_next' => $page < $executions->getLastPage(),
        ]);
    }

    #[Route('/admin/module/WebScheduler/executions/{id}', name: 'webscheduler.admin.execution.detail', requirements: ['id' => '\d+'])]
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
            'execution' => $this->presentDetail($execution),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRow(WebSchedulerExecution $execution, UrlGeneratorInterface $urlGenerator): array
    {
        $status = (string) $execution->getStatus();

        return [
            'id' => $execution->getId(),
            'task' => $execution->getWebSchedulerTask()?->getTitle() ?? '',
            'triggered_at' => $execution->getTriggeredAt()?->format(self::DATE_FORMAT) ?? '',
            'status_label' => $this->translateSlug($status),
            'status_variant' => self::STATUS_VARIANTS[$status] ?? 'danger',
            'strategy' => $this->translateSlug((string) $execution->getStrategyUsed()),
            'exit_code' => $execution->getExitCode(),
            'duration' => $this->durationOf($execution),
            'trigger_ip' => (string) $execution->getTriggerIp(),
            'detail_url' => $urlGenerator->generate('webscheduler.admin.execution.detail', ['id' => $execution->getId()]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentDetail(WebSchedulerExecution $execution): array
    {
        $status = (string) $execution->getStatus();
        $task = $execution->getWebSchedulerTask();

        return [
            'id' => $execution->getId(),
            'task' => null === $task ? '' : \sprintf('%s (%s)', $task->getTitle(), $task->getCommandName()),
            'status_label' => $this->translateSlug($status),
            'status_variant' => self::STATUS_VARIANTS[$status] ?? 'danger',
            'strategy' => $this->translateSlug((string) $execution->getStrategyUsed()),
            'exit_code' => $execution->getExitCode(),
            'triggered_at' => $execution->getTriggeredAt()?->format(self::DATE_FORMAT) ?? '',
            'started_at' => $execution->getStartedAt()?->format(self::DATE_FORMAT) ?? '',
            'finished_at' => $execution->getFinishedAt()?->format(self::DATE_FORMAT) ?? '',
            'trigger_ip' => (string) $execution->getTriggerIp(),
            'output' => (string) $execution->getOutput(),
        ];
    }

    private function durationOf(WebSchedulerExecution $execution): string
    {
        $startedAt = $execution->getStartedAt();
        $finishedAt = $execution->getFinishedAt();

        if (null === $startedAt || null === $finishedAt) {
            return '';
        }

        return ($finishedAt->getTimestamp() - $startedAt->getTimestamp()).' s';
    }

    /**
     * Statuses and strategy names are stored as raw slugs; the module catalogue carries a
     * human label for each. An unknown slug falls back to itself, never to English.
     */
    private function translateSlug(string $value): string
    {
        if ('' === $value) {
            return '';
        }

        return $this->getTranslator()->trans($value, [], WebScheduler::DOMAIN_NAME);
    }
}
