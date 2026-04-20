<?php

declare(strict_types=1);

namespace WebScheduler\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebScheduler\Enum\ExecutionStatusEnum;
use WebScheduler\Model\WebSchedulerExecutionQuery;
use WebScheduler\WebScheduler;

#[AsCommand(
    name: 'web-scheduler:finalize-execution',
    description: 'Mark a detached execution as finished and record its output.',
    hidden: true,
)]
final class FinalizeExecutionCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Execution id')
            ->addOption('rc', null, InputOption::VALUE_REQUIRED, 'Exit code returned by the child command', '0')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Path to the captured output file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getOption('id');
        $exitCode = (int) $input->getOption('rc');
        $outputFile = (string) $input->getOption('output-file');

        if ($id <= 0) {
            $output->writeln('<error>Missing or invalid --id.</error>');

            return Command::INVALID;
        }

        $execution = WebSchedulerExecutionQuery::create()->findPk($id);

        if (null === $execution) {
            $output->writeln(sprintf('<error>Execution #%d not found.</error>', $id));

            return Command::FAILURE;
        }

        $captured = '' !== $outputFile && is_file($outputFile)
            ? (string) file_get_contents($outputFile)
            : '';

        $execution
            ->setFinishedAt(new \DateTimeImmutable())
            ->setExitCode($exitCode)
            ->setStatus((0 === $exitCode ? ExecutionStatusEnum::SUCCESS : ExecutionStatusEnum::FAILED)->value)
            ->setOutput($this->truncate($captured))
            ->save();

        if ('' !== $outputFile && is_file($outputFile)) {
            @unlink($outputFile);
        }

        return Command::SUCCESS;
    }

    private function truncate(string $output): string
    {
        if ('' === $output) {
            return '';
        }

        if (\strlen($output) <= WebScheduler::OUTPUT_MAX_BYTES) {
            return $output;
        }

        return substr($output, 0, WebScheduler::OUTPUT_MAX_BYTES).\PHP_EOL.'[truncated]';
    }
}
