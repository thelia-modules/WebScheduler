<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution\Strategy;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use WebScheduler\Enum\CapabilityEnum;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Service\Capability\CapabilityReport;
use WebScheduler\Service\Execution\ExecutionContext;
use WebScheduler\Service\Execution\ExecutionResult;
use WebScheduler\Service\Execution\ExecutionStrategyInterface;
use WebScheduler\Service\Execution\ExternalProcessFactory;
use WebScheduler\Service\Execution\InProcessCommandRunner;
use WebScheduler\WebScheduler;

final readonly class FastCgiFinishStrategy implements ExecutionStrategyInterface
{
    public function __construct(
        private ExternalProcessFactory $processFactory,
        private InProcessCommandRunner $inProcessRunner,
    ) {
    }

    public function name(): ExecutionStrategyEnum
    {
        return ExecutionStrategyEnum::FASTCGI_FINISH;
    }

    public function priority(): int
    {
        return 50;
    }

    public function supports(CapabilityReport $report): bool
    {
        return $report->isAvailable(CapabilityEnum::FASTCGI_FINISH_REQUEST)
            && $report->isAvailable(CapabilityEnum::IGNORE_USER_ABORT);
    }

    public function execute(ExecutionContext $context): ExecutionResult
    {
        $this->detachFromClient();

        $task = $context->task;
        $canProcess = \function_exists('proc_open');

        if ($canProcess) {
            $process = $this->processFactory->create($task, $this->resolveTimeout($task->getMaxRuntimeSeconds()));
            $buffer = '';

            try {
                $exitCode = $process->run(static function (string $type, string $chunk) use (&$buffer): void {
                    $buffer .= $chunk;
                });
            } catch (ProcessTimedOutException) {
                return ExecutionResult::timedOut($this->name(), $this->truncate($buffer));
            } catch (\Throwable $e) {
                return ExecutionResult::failed($this->name(), $this->truncate($buffer.\PHP_EOL.$e->getMessage()));
            }

            return ExecutionResult::completed($this->name(), (int) $exitCode, $this->truncate($buffer));
        }

        $result = $this->inProcessRunner->run($task);

        return ExecutionResult::completed($this->name(), $result->exitCode, $result->output);
    }

    private function detachFromClient(): void
    {
        ignore_user_abort(true);

        if (\function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        if (\function_exists('session_write_close')) {
            @session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();

        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    private function resolveTimeout(int $maxRuntimeSeconds): ?float
    {
        return $maxRuntimeSeconds > 0 ? (float) $maxRuntimeSeconds : null;
    }

    private function truncate(string $output): string
    {
        if (\strlen($output) <= WebScheduler::OUTPUT_MAX_BYTES) {
            return $output;
        }

        return substr($output, 0, WebScheduler::OUTPUT_MAX_BYTES).\PHP_EOL.'[truncated]';
    }
}
