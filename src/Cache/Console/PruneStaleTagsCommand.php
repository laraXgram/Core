<?php

namespace LaraGram\Cache\Console;

use LaraGram\Cache\CacheManager;
use LaraGram\Cache\RedisStore;
use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputArgument;

#[AsCommand(name: 'cache:prune-stale-tags')]
class PruneStaleTagsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:prune-stale-tags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale cache tags from the cache (Redis only)';

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Cache\CacheManager  $cache
     * @return int|null
     */
    public function handle(CacheManager $cache)
    {
        $cache = $cache->store($this->argument('store'));

        if (! $cache->getStore() instanceof RedisStore) {
            $this->components->error('Pruning cache tags is only necessary when using Redis.');

            return 1;
        }

        $cache->flushStaleTags();

        $this->components->info('Stale cache tags pruned successfully.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['store', InputArgument::OPTIONAL, 'The name of the store you would like to prune tags from'],
        ];
    }
}
