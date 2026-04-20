<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Thelia\Core\Application as TheliaApplication;
use WebScheduler\Model\WebSchedulerTask;
use WebScheduler\WebScheduler;

final readonly class InProcessCommandRunner
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    public function run(WebSchedulerTask $task): InProcessRunResult
    {
        $application = new TheliaApplication($this->kernel);
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $input = new StringInput(trim(sprintf(
            '%s %s',
            $task->getCommandName(),
            (string) $task->getCommandArguments(),
        )));

        $output = new BufferedOutput();

        try {
            $exitCode = $application->run($input, $output);
        } catch (\Throwable $e) {
            return new InProcessRunResult(
                exitCode: 1,
                output: $this->truncate($output->fetch().\PHP_EOL.$e::class.': '.$e->getMessage()),
                failed: true,
            );
        }

        return new InProcessRunResult(
            exitCode: $exitCode,
            output: $this->truncate($output->fetch()),
            failed: false,
        );
    }

    private function truncate(string $output): string
    {
        if (\strlen($output) <= WebScheduler::OUTPUT_MAX_BYTES) {
            return $output;
        }

        return substr($output, 0, WebScheduler::OUTPUT_MAX_BYTES).\PHP_EOL.'[truncated]';
    }
}
