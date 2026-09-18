<?php

namespace LaraGram\Broadcasting\Console;

use LaraGram\Broadcasting\BroadcastException;
use LaraGram\Broadcasting\BroadcastManager;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Command;

use function LaraGram\Console\Prompts\confirm;

#[AsCommand(name: 'broadcast:recall')]
class RecallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:recall
                    {id : The identifier of a recallable broadcast}
                    {--partial : Skip the calls that cannot be undone instead of failing}
                    {--force : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Undo a Telegram broadcast: delete what it sent, unpin, unban, and so on';

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return int
     */
    public function handle(BroadcastManager $manager)
    {
        $id = $this->argument('id');

        if (! $this->option('force') && ! confirm("Undo broadcast [{$id}] in every chat it reached?", default: false)) {
            return 1;
        }

        try {
            $recall = $manager->recall($id, (bool) $this->option('partial'));
        } catch (BroadcastException $e) {
            $this->components->error($e->getMessage());

            return 1;
        }

        $this->components->info("Recalling broadcast [{$id}] as broadcast [{$recall}].");

        return 0;
    }
}
