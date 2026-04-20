<?php

declare(strict_types=1);

namespace WebScheduler\Service\Security;

use Symfony\Component\HttpFoundation\Request;
use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Model\WebSchedulerTaskQuery;

final readonly class TriggerAuthenticator
{
    public function __construct(
        private HmacSigner $signer,
        private IpMatcher $ipMatcher,
    ) {
    }

    public function authenticate(string $slug, Request $request): AuthenticationResult
    {
        $task = WebSchedulerTaskQuery::create()->findOneBySlug($slug);

        if (null === $task) {
            return AuthenticationResult::denied(ExecutionStatusEnum::UNAUTHORIZED, 'Unknown slug.');
        }

        if (!$task->getEnabled()) {
            return AuthenticationResult::denied(ExecutionStatusEnum::DISABLED, 'Task is disabled.');
        }

        $timestamp = (int) $request->query->get('ts', '0');
        $signature = (string) $request->query->get('sig', '');

        if (0 === $timestamp || '' === $signature) {
            return AuthenticationResult::denied(ExecutionStatusEnum::UNAUTHORIZED, 'Missing signature.');
        }

        if (!$this->signer->verify($slug, $task->getSecret(), $timestamp, $signature)) {
            return AuthenticationResult::denied(ExecutionStatusEnum::UNAUTHORIZED, 'Invalid signature.');
        }

        $allowlist = $this->ipMatcher->parse($task->getIpAllowlist());

        if (!$this->ipMatcher->matches($request->getClientIp(), $allowlist)) {
            return AuthenticationResult::denied(ExecutionStatusEnum::IP_DENIED, 'IP not allowed.');
        }

        if ($this->isRateLimited($task)) {
            return AuthenticationResult::denied(ExecutionStatusEnum::RATE_LIMITED, 'Rate limit exceeded.');
        }

        return AuthenticationResult::granted($task);
    }

    private function isRateLimited(\WebScheduler\Model\WebSchedulerTask $task): bool
    {
        $interval = $task->getMinIntervalSeconds();

        if ($interval <= 0) {
            return false;
        }

        $lastTriggered = $task->getLastTriggeredAt(\DateTimeImmutable::class);

        if (!$lastTriggered instanceof \DateTimeImmutable) {
            return false;
        }

        return (new \DateTimeImmutable())->getTimestamp() - $lastTriggered->getTimestamp() < $interval;
    }
}
