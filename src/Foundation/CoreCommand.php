<?php

namespace LaraGram\Foundation;

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
            Console\GenerateCommand::class,
            Console\GenerateFactory::class,
            Console\GenerateMigration::class,
            Console\GenerateModel::class,
            Console\GenerateSeeder::class,
            Console\MigrateCommand::class,
            Console\SeederCommand::class,
            Console\GenerateJsonMigration::class,
            Console\GenerateJsonModel::class,
            Console\JsonMigrateCommand::class,
            Console\GenerateProvider::class,
            Console\GenerateResource::class,
            Console\SetWebhookCommand::class,
            Console\DeleteWebhookCommand::class,
            Console\DropWebhookCommand::class,
            Console\WebhookInfoCommand::class,
            Console\GenerateFacade::class,
            Console\GenerateClass::class,
            Console\GenerateEnum::class,
            Console\ServeCommand::class,
            Console\APIServeCommand::class,
            Console\GenerateController::class,
            Console\InstallEloquent::class,
            Console\InstallOpenSwoole::class,
            Console\UninstallOpenSwoole::class,
            Console\UninstallEloquent::class,
            Console\GenerateConversation::class,
            Console\ConfigCacheCommand::class,
            Console\ConfigClearCommand::class,
        ];
    }
}