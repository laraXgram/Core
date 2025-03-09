<?php

namespace LaraGram\Queue\Console;

use LaraGram\Bus\BatchRepository;
use LaraGram\Bus\DatabaseBatchRepository;
use LaraGram\Bus\PrunableBatchRepository;
use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:prune-batches')]
class PruneBatchesCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:prune-batches
                {--hours=24 : The number of hours to retain batch data}
                {--unfinished= : The number of hours to retain unfinished batch data }
                {--cancelled= : The number of hours to retain cancelled batch data }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale entries from the batches database';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $repository = $this->laragram[BatchRepository::class];

        $count = 0;

        if ($repository instanceof PrunableBatchRepository) {
            $count = $repository->prune((new \DateTime())->modify('-' . $this->option('hours') . ' hours'));
        }

        $this->components->info("{$count} entries deleted.");

        if ($this->option('unfinished') !== null) {
            $count = 0;

            if ($repository instanceof DatabaseBatchRepository) {
                $count = $repository->pruneUnfinished((new \DateTime())->modify('-' . $this->option('unfinished') . ' hours'));
            }

            $this->components->info("{$count} unfinished entries deleted.");
        }

        if ($this->option('cancelled') !== null) {
            $count = 0;

            if ($repository instanceof DatabaseBatchRepository) {
                $count = $repository->pruneCancelled((new \DateTime())->modify('-' . $this->option('cancelled') . ' hours'));
            }

            $this->components->info("{$count} cancelled entries deleted.");
        }
    }
}
