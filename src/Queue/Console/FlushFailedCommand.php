<?php

namespace LaraGram\Queue\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Prohibitable;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:flush')]
class FlushFailedCommand extends Command
{
    use Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:flush {--hours= : The number of hours to retain failed job data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all of the failed queue jobs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isProhibited()) {
            return;
        }

        $this->laragram['queue.failer']->flush($this->option('hours'));

        if ($this->option('hours')) {
            $this->components->info("All jobs that failed more than {$this->option('hours')} hours ago have been deleted successfully.");

            return;
        }

        $this->components->info('All failed jobs deleted successfully.');
    }
}
