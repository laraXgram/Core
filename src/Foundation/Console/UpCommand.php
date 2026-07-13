<?php

namespace LaraGram\Foundation\Console;

use Exception;
use LaraGram\Console\Command;
use LaraGram\Foundation\Events\MaintenanceModeDisabled;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'up')]
class UpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bring the application out of maintenance mode';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            if (! $this->laragram->maintenanceMode()->active()) {
                $this->components->info('Application is already up.');

                return 0;
            }

            $this->laragram->maintenanceMode()->deactivate();

            if (is_file(storage_path('framework/maintenance.php'))) {
                unlink(storage_path('framework/maintenance.php'));
            }

            $this->laragram->get('events')->dispatch(new MaintenanceModeDisabled());

            $this->components->info('Application is now live.');
        } catch (Exception $e) {
            report($e);

            $this->components->error(sprintf(
                'Failed to disable maintenance mode: %s.',
                $e->getMessage(),
            ));

            return 1;
        }

        return 0;
    }
}
