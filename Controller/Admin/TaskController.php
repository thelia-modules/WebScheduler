<?php

declare(strict_types=1);

namespace WebScheduler\Controller\Admin;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Tools\URL;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Model\WebSchedulerTask;
use WebScheduler\Model\WebSchedulerTaskQuery;
use WebScheduler\Service\Command\CommandRegistry;
use WebScheduler\Service\Task\TaskPersister;
use WebScheduler\Service\Task\TaskRunner;
use WebScheduler\Service\Url\TriggerUrlBuilder;
use WebScheduler\WebScheduler;

class TaskController extends BaseAdminController
{
    public const MODULE_CODE = 'WebScheduler';

    public function listAction(CommandRegistry $commandRegistry, TriggerUrlBuilder $urlBuilder): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::VIEW)) {
            return $response;
        }

        $tasks = WebSchedulerTaskQuery::create()->orderByTitle()->find();

        $taskViews = [];
        foreach ($tasks as $task) {
            $taskViews[] = [
                'task' => $task,
                'trigger_url' => $urlBuilder->build($task),
            ];
        }

        return $this->render('webscheduler/task-list', [
            'tasks' => $taskViews,
            'available_commands' => $commandRegistry->all(),
        ]);
    }

    public function createAction(Request $request, TaskPersister $persister, CommandRegistry $commandRegistry): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::CREATE)) {
            return $response;
        }

        if ($request->isMethod('POST')) {
            $form = $this->createForm('webscheduler.task.form');

            try {
                $vform = $this->validateForm($form);
                $task = $persister->create($vform->getData());

                return new RedirectResponse(URL::getInstance()?->absoluteUrl(
                    sprintf('/admin/module/WebScheduler/tasks/%d?secret_revealed=1', $task->getId()),
                ) ?? '/admin/module/WebScheduler');
            } catch (\Throwable $e) {
                $this->setupFormErrorContext('webscheduler.task.form', $e->getMessage(), $form ?? null);
            }
        }

        return $this->render('webscheduler/task-form', [
            'form_mode' => 'create',
            'task' => null,
            'secret_revealed' => null,
            'available_commands' => $commandRegistry->all(),
            'task_data' => $this->defaultTaskData(),
        ]);
    }

    public function updateAction(
        int $id,
        Request $request,
        TaskPersister $persister,
        CommandRegistry $commandRegistry,
        TriggerUrlBuilder $urlBuilder,
    ): Response {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null === $task) {
            return $this->errorPage($this->getTranslator()->trans('Task not found.', [], WebScheduler::DOMAIN_NAME), 404);
        }

        if ($request->isMethod('POST')) {
            $form = $this->createForm('webscheduler.task.form');

            try {
                $vform = $this->validateForm($form);
                $persister->update($task, $vform->getData());

                return new RedirectResponse(URL::getInstance()?->absoluteUrl(
                    sprintf('/admin/module/WebScheduler/tasks/%d', $task->getId()),
                ) ?? '/admin/module/WebScheduler');
            } catch (\Throwable $e) {
                $this->setupFormErrorContext('webscheduler.task.form', $e->getMessage(), $form ?? null);
            }
        }

        return $this->render('webscheduler/task-form', [
            'form_mode' => 'update',
            'task' => $task,
            'trigger_url' => $urlBuilder->build($task),
            'secret_revealed' => '1' === $request->query->get('secret_revealed') ? $task->getSecret() : null,
            'available_commands' => $commandRegistry->all(),
            'task_data' => $this->taskDataFrom($task),
        ]);
    }

    public function deleteAction(int $id): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::DELETE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null !== $task) {
            $task->delete();
        }

        return new RedirectResponse(URL::getInstance()?->absoluteUrl('/admin/module/WebScheduler') ?? '/admin/module/WebScheduler');
    }

    public function toggleAction(int $id): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null !== $task) {
            $task->setEnabled(!$task->getEnabled())->save();
        }

        return new RedirectResponse(URL::getInstance()?->absoluteUrl('/admin/module/WebScheduler') ?? '/admin/module/WebScheduler');
    }

    public function regenerateSecretAction(int $id, TaskPersister $persister): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null === $task) {
            return new RedirectResponse(URL::getInstance()?->absoluteUrl('/admin/module/WebScheduler') ?? '/admin/module/WebScheduler');
        }

        $persister->regenerateSecret($task);

        return new RedirectResponse(URL::getInstance()?->absoluteUrl(
            sprintf('/admin/module/WebScheduler/tasks/%d?secret_revealed=1', $task->getId()),
        ) ?? '/admin/module/WebScheduler');
    }

    public function manualTriggerAction(int $id, Request $request, TaskRunner $runner): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null !== $task) {
            $runner->run($task, $request->getClientIp());
        }

        return new RedirectResponse(URL::getInstance()?->absoluteUrl('/admin/module/WebScheduler') ?? '/admin/module/WebScheduler');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultTaskData(): array
    {
        return [
            'title' => '',
            'command_name' => '',
            'command_arguments' => '',
            'strategy' => ExecutionStrategyEnum::AUTO->value,
            'enabled' => true,
            'min_interval_seconds' => 0,
            'max_runtime_seconds' => 0,
            'ip_allowlist' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taskDataFrom(WebSchedulerTask $task): array
    {
        return [
            'title' => $task->getTitle(),
            'command_name' => $task->getCommandName(),
            'command_arguments' => $task->getCommandArguments() ?? '',
            'strategy' => $task->getStrategy(),
            'enabled' => $task->getEnabled(),
            'min_interval_seconds' => $task->getMinIntervalSeconds(),
            'max_runtime_seconds' => $task->getMaxRuntimeSeconds(),
            'ip_allowlist' => $this->ipAllowlistAsText($task->getIpAllowlist()),
        ];
    }

    private function ipAllowlistAsText(?string $payload): string
    {
        if (null === $payload || '' === $payload) {
            return '';
        }

        $decoded = json_decode($payload, true);

        return \is_array($decoded) ? implode("\n", $decoded) : $payload;
    }
}
