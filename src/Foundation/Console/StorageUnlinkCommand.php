<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'storage:unlink')]
class StorageUnlinkCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:unlink';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete existing symbolic links configured for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->links() as $link => $target) {
            if (! file_exists($link) || ! is_link($link)) {
                continue;
            }

            $this->laragram->make('files')->delete($link);

            $this->components->info("The [$link] link has been deleted.");
        }
    }

    /**
     * Get the symbolic links that are configured for the application.
     *
     * @return array
     */
    protected function links()
    {
        return $this->laragram['config']['filesystems.links'] ??
            [$this->laragram->assetsPath('storage') => $this->laragram->storagePath('app/public')];
    }
}
