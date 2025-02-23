<?php

namespace LaraGram\Console\Scheduling;

use DateTime;
use LaraGram\Console\Application;
use LaraGram\Console\Command;
use LaraGram\Support\ProcessUtils;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Console\Process\Process;

#[AsCommand(name: 'schedule:work')]
class ScheduleWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:work {--run-output-file= : The file to direct <info>schedule:run</info> output to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the schedule worker';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->components->info('Running scheduled tasks.');

        $lastExecutionStartedAt = new DateTime();
        $lastExecutionStartedAt->modify('-10 minutes');
        $executions = [];

        $command = Application::formatCommandString('schedule:run');

        if ($this->option('run-output-file')) {
            $command .= ' >> '.ProcessUtils::escapeArgument($this->option('run-output-file')).' 2>&1';
        }

        while (true) {
            usleep(100 * 1000);

            $now = new DateTime();

            $lastExecutionStartedAtStartOfMinute = clone $lastExecutionStartedAt;
            $lastExecutionStartedAtStartOfMinute->setTime((int)$lastExecutionStartedAt->format('H'), (int)$lastExecutionStartedAt->format('i'), 0);

            if ($now->format('s') === '00' && $now != $lastExecutionStartedAtStartOfMinute) {
                $executions[] = $execution = Process::fromShellCommandline($command);

                $execution->start();

                $lastExecutionStartedAt = new DateTime();
                $lastExecutionStartedAt->setTime((int)$now->format('H'), (int)$now->format('i'), 0);
            }

            foreach ($executions as $key => $execution) {
                $output = $execution->getIncrementalOutput().
                    $execution->getIncrementalErrorOutput();

                $this->output->write(ltrim($output, "\n"));

                if (! $execution->isRunning()) {
                    unset($executions[$key]);
                }
            }
        }
    }
}
