<?php

declare(strict_types=1);

namespace WebScheduler\Service\Task;

use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Model\WebSchedulerTask;
use WebScheduler\Service\Command\CommandValidator;
use WebScheduler\Service\Security\IpMatcher;
use WebScheduler\Service\Security\SecretGenerator;

final readonly class TaskPersister
{
    public function __construct(
        private CommandValidator $commandValidator,
        private SecretGenerator $secretGenerator,
        private IpMatcher $ipMatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): WebSchedulerTask
    {
        $this->commandValidator->validate(
            (string) $data['command_name'],
            $this->stringOrNull($data['command_arguments'] ?? null),
        );

        $task = (new WebSchedulerTask())
            ->setSlug($this->secretGenerator->generateSlug())
            ->setSecret($this->secretGenerator->generateSecret());

        $this->hydrate($task, $data);
        $task->save();

        return $task;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(WebSchedulerTask $task, array $data): WebSchedulerTask
    {
        $this->commandValidator->validate(
            (string) $data['command_name'],
            $this->stringOrNull($data['command_arguments'] ?? null),
        );

        $this->hydrate($task, $data);
        $task->save();

        return $task;
    }

    public function regenerateSecret(WebSchedulerTask $task): string
    {
        $secret = $this->secretGenerator->generateSecret();
        $task->setSecret($secret)->save();

        return $secret;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(WebSchedulerTask $task, array $data): void
    {
        $strategy = ExecutionStrategyEnum::tryFrom((string) ($data['strategy'] ?? 'auto')) ?? ExecutionStrategyEnum::AUTO;

        $task
            ->setTitle((string) $data['title'])
            ->setCommandName((string) $data['command_name'])
            ->setCommandArguments($this->stringOrNull($data['command_arguments'] ?? null))
            ->setStrategy($strategy->value)
            ->setEnabled((bool) ($data['enabled'] ?? false))
            ->setMinIntervalSeconds((int) ($data['min_interval_seconds'] ?? 0))
            ->setMaxRuntimeSeconds((int) ($data['max_runtime_seconds'] ?? 0))
            ->setIpAllowlist($this->ipMatcher->serialize($this->ipMatcher->parse($this->stringOrNull($data['ip_allowlist'] ?? null))));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
