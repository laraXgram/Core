<?php

namespace LaraGram\Console\Scheduling;

use DateTime;
use LaraGram\Console\Command;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:interrupt')]
class ScheduleInterruptCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:interrupt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interrupt the current schedule run';

    /**
     * The cache store implementation.
     *
     * @var \LaraGram\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new schedule interrupt command.
     *
     * @param  \LaraGram\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->cache->put('LaraGram:schedule:interrupt', true, (new DateTime())->modify('last minute of this hour'));

        $this->components->info('Broadcasting schedule interrupt signal.');
    }
}
