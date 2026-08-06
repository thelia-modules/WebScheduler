<?php

declare(strict_types=1);

namespace WebScheduler\Controller\Admin;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
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

    #[Route('/admin/module/WebScheduler', name: 'webscheduler.admin')]
    #[Route('/admin/module/WebScheduler/tasks', name: 'webscheduler.admin.task.list')]
    public function listAction(
        CommandRegistry $commandRegistry,
        TriggerUrlBuilder $urlBuilder,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::VIEW)) {
            return $response;
        }

        $tasks = WebSchedulerTaskQuery::create()->orderByTitle()->find();

        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = $this->presentRow($task, $urlBuilder, $urlGenerator);
        }

        return $this->render('webscheduler/task-list', [
            'tasks' => $rows,
            'available_commands' => $commandRegistry->all(),
        ]);
    }

    #[Route('/admin/module/WebScheduler/tasks/create', name: 'webscheduler.admin.task.create', methods: ['GET', 'POST'])]
    public function createAction(
        Request $request,
        TaskPersister $persister,
        CommandRegistry $commandRegistry,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::CREATE)) {
            return $response;
        }

        $form = $this->createForm('webscheduler.task.form', FormType::class, $this->defaultTaskData());

        if ($request->isMethod('POST')) {
            try {
                $vform = $this->validateForm($form);
                $task = $persister->create($vform->getData());

                return new RedirectResponse($urlGenerator->generate(
                    'webscheduler.admin.task.update',
                    ['id' => $task->getId(), 'secret_revealed' => 1],
                ));
            } catch (\Throwable $e) {
                $this->setupFormErrorContext('webscheduler.task.form', $e->getMessage(), $form);
            }
        }

        return $this->render('webscheduler/task-form', [
            'page_title' => $this->getTranslator()->trans('Create a task', [], WebScheduler::DOMAIN_NAME),
            'form' => $form->getForm()->createView(),
            'form_action' => $urlGenerator->generate('webscheduler.admin.task.create'),
            'trigger_url' => null,
            'regenerate_secret_url' => null,
            'secret_revealed' => null,
            'available_commands' => $commandRegistry->all(),
        ]);
    }

    #[Route('/admin/module/WebScheduler/tasks/{id}', name: 'webscheduler.admin.task.update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function updateAction(
        int $id,
        Request $request,
        TaskPersister $persister,
        CommandRegistry $commandRegistry,
        TriggerUrlBuilder $urlBuilder,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null === $task) {
            return $this->errorPage($this->getTranslator()->trans('Task not found.', [], WebScheduler::DOMAIN_NAME), 404);
        }

        $form = $this->createForm('webscheduler.task.form', FormType::class, $this->taskDataFrom($task));

        if ($request->isMethod('POST')) {
            try {
                $vform = $this->validateForm($form);
                $persister->update($task, $vform->getData());

                return new RedirectResponse($urlGenerator->generate('webscheduler.admin.task.update', ['id' => $task->getId()]));
            } catch (\Throwable $e) {
                $this->setupFormErrorContext('webscheduler.task.form', $e->getMessage(), $form);
            }
        }

        return $this->render('webscheduler/task-form', [
            'page_title' => $task->getTitle(),
            'form' => $form->getForm()->createView(),
            'form_action' => $urlGenerator->generate('webscheduler.admin.task.update', ['id' => $task->getId()]),
            'trigger_url' => $urlBuilder->build($task),
            'regenerate_secret_url' => $urlGenerator->generate('webscheduler.admin.task.regenerate-secret', ['id' => $task->getId()]),
            'secret_revealed' => '1' === $request->query->get('secret_revealed') ? $task->getSecret() : null,
            'available_commands' => $commandRegistry->all(),
        ]);
    }

    #[Route('/admin/module/WebScheduler/tasks/{id}/delete', name: 'webscheduler.admin.task.delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteAction(int $id, UrlGeneratorInterface $urlGenerator): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::DELETE)) {
            return $response;
        }

        WebSchedulerTaskQuery::create()->findPk($id)?->delete();

        return new RedirectResponse($urlGenerator->generate('webscheduler.admin.task.list'));
    }

    #[Route('/admin/module/WebScheduler/tasks/{id}/toggle', name: 'webscheduler.admin.task.toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleAction(int $id, UrlGeneratorInterface $urlGenerator): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null !== $task) {
            $task->setEnabled(!$task->getEnabled())->save();
        }

        return new RedirectResponse($urlGenerator->generate('webscheduler.admin.task.list'));
    }

    #[Route('/admin/module/WebScheduler/tasks/{id}/regenerate-secret', name: 'webscheduler.admin.task.regenerate-secret', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function regenerateSecretAction(int $id, TaskPersister $persister, UrlGeneratorInterface $urlGenerator): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null === $task) {
            return new RedirectResponse($urlGenerator->generate('webscheduler.admin.task.list'));
        }

        $persister->regenerateSecret($task);

        return new RedirectResponse($urlGenerator->generate(
            'webscheduler.admin.task.update',
            ['id' => $task->getId(), 'secret_revealed' => 1],
        ));
    }

    #[Route('/admin/module/WebScheduler/tasks/{id}/trigger', name: 'webscheduler.admin.task.trigger', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function manualTriggerAction(int $id, Request $request, TaskRunner $runner, UrlGeneratorInterface $urlGenerator): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, self::MODULE_CODE, AccessManager::UPDATE)) {
            return $response;
        }

        $task = WebSchedulerTaskQuery::create()->findPk($id);

        if (null !== $task) {
            $runner->run($task, $request->getClientIp());
        }

        return new RedirectResponse($urlGenerator->generate('webscheduler.admin.task.list'));
    }

    /**
     * One row of the task list: every URL and every label is resolved here so the template
     * only lays out already-final strings.
     *
     * @return array<string, mixed>
     */
    private function presentRow(
        WebSchedulerTask $task,
        TriggerUrlBuilder $urlBuilder,
        UrlGeneratorInterface $urlGenerator,
    ): array {
        $strategy = ExecutionStrategyEnum::tryFrom((string) $task->getStrategy());

        return [
            'title' => $task->getTitle(),
            'command_name' => $task->getCommandName(),
            'command_arguments' => (string) $task->getCommandArguments(),
            'strategy_label' => null === $strategy
                ? (string) $task->getStrategy()
                : $this->getTranslator()->trans($strategy->label(), [], WebScheduler::DOMAIN_NAME),
            'enabled' => (bool) $task->getEnabled(),
            'last_triggered_at' => $task->getLastTriggeredAt()?->format('Y-m-d H:i:s') ?? '',
            'trigger_url' => $urlBuilder->build($task),
            'edit_url' => $urlGenerator->generate('webscheduler.admin.task.update', ['id' => $task->getId()]),
            'trigger_action_url' => $urlGenerator->generate('webscheduler.admin.task.trigger', ['id' => $task->getId()]),
            'toggle_url' => $urlGenerator->generate('webscheduler.admin.task.toggle', ['id' => $task->getId()]),
            'delete_url' => $urlGenerator->generate('webscheduler.admin.task.delete', ['id' => $task->getId()]),
        ];
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
