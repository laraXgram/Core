<?php

namespace LaraGram\Foundation;

use LaraGram\Console\Console;

class CoreCommand
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerBaseCommands();
    }

    public function getCoreCommands(): array
    {
        return $this->commands;
    }

    private function registerBaseCommands(): void
    {
        foreach ($this->baseCommands() as $command) {
            $this->append($command);
        }
    }

    public function append($command): static
    {
        $this->commands[] = $command;
        return $this;
    }

    private function baseCommands(): array
    {
        return [
            \LaraGram\Foundation\Console\GenerateCommand::class,
            \LaraGram\Foundation\Console\GenerateFactory::class,
            \LaraGram\Foundation\Console\GenerateMigration::class,
            \LaraGram\Foundation\Console\GenerateModel::class,
            \LaraGram\Foundation\Console\GenerateSeeder::class,
            \LaraGram\Foundation\Console\MigrateCommand::class,
            \LaraGram\Foundation\Console\SeederCommand::class,
            \LaraGram\Foundation\Console\GenerateJsonMigration::class,
            \LaraGram\Foundation\Console\GenerateJsonModel::class,
            \LaraGram\Foundation\Console\JsonMigrateCommand::class,
            \LaraGram\Foundation\Console\GenerateProvider::class,
            \LaraGram\Foundation\Console\GenerateResource::class,
            \LaraGram\Foundation\Console\SetWebhookCommand::class,
            \LaraGram\Foundation\Console\DeleteWebhookCommand::class,
            \LaraGram\Foundation\Console\DropWebhookCommand::class,
            \LaraGram\Foundation\Console\WebhookInfoCommand::class,
            \LaraGram\Foundation\Console\GenerateFacade::class,
            \LaraGram\Foundation\Console\GenerateClass::class,
            \LaraGram\Foundation\Console\GenerateEnum::class,
            \LaraGram\Foundation\Console\ServeCommand::class,
            \LaraGram\Foundation\Console\APIServeCommand::class,
            \LaraGram\Foundation\Console\GenerateController::class,
            \LaraGram\Foundation\Console\InstallEloquent::class,
            \LaraGram\Foundation\Console\InstallOpenSwoole::class,
            \LaraGram\Foundation\Console\UninstallOpenSwoole::class,
            \LaraGram\Foundation\Console\UninstallEloquent::class,
            \LaraGram\Foundation\Console\GenerateConversation::class,
            \LaraGram\Foundation\Console\ConfigCacheCommand::class,
            \LaraGram\Foundation\Console\ConfigClearCommand::class,
            \LaraGram\Foundation\Console\EventCacheCommand::class,
            \LaraGram\Foundation\Console\EventClearCommand::class,
        ];
    }
}