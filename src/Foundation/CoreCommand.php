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
            \LaraGram\Console\GenerateCommand::class,
            \LaraGram\Database\Factories\GenerateFactory::class,
            \LaraGram\Database\Migrations\GenerateMigration::class,
            \LaraGram\Database\Models\GenerateModel::class,
            \LaraGram\Database\Seeders\GenerateSeeder::class,
            \LaraGram\Database\Migrations\Migrator\MigrateCommand::class,
            \LaraGram\Database\Seeders\SeederCommand::class,
            \LaraGram\JsonDatabase\Migrations\GenerateMigration::class,
            \LaraGram\JsonDatabase\Models\GenerateModel::class,
            \LaraGram\JsonDatabase\MigrateCommand::class,
            \LaraGram\Foundation\Provider\GenerateProvider::class,
            \LaraGram\Foundation\Resource\GenerateResource::class,
            \LaraGram\Foundation\Webhook\SetWebhookCommand::class,
            \LaraGram\Foundation\Webhook\DeleteWebhookCommand::class,
            \LaraGram\Foundation\Webhook\DropWebhookCommand::class,
            \LaraGram\Foundation\Webhook\WebhookInfoCommand::class,
            \LaraGram\Foundation\Objects\Facade\GenerateFacade::class,
            \LaraGram\Foundation\Objects\Class\GenerateClass::class,
            \LaraGram\Foundation\Objects\Enum\GenerateEnum::class,
            \LaraGram\Foundation\Server\ServeCommand::class,
            \LaraGram\Foundation\Server\APIServeCommand::class,
            \LaraGram\Foundation\Objects\Controller\GenerateController::class,
            \LaraGram\Foundation\PackageManager\Installer\Eloquent::class,
            \LaraGram\Foundation\PackageManager\Installer\OpenSwoole::class,
            \LaraGram\Foundation\PackageManager\Uninstaller\OpenSwoole::class,
            \LaraGram\Foundation\PackageManager\Uninstaller\Eloquent::class,
        ];
    }
}