<?php

namespace LaraGram\Broadcasting\Console;

use Closure;
use LaraGram\Broadcasting\BroadcastManager;
use LaraGram\Broadcasting\Telegram\Audience;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Command;
use Throwable;

#[AsCommand(name: 'channel:list')]
class ChannelListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:list
                    {--count : Count the reachable chats of the built-in Telegram audiences}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the Telegram audiences and the private WebSocket channels';

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return int
     */
    public function handle(BroadcastManager $manager)
    {
        $this->displayAudiences($manager);

        $this->displayChannels($manager);

        return 0;
    }

    /**
     * Display the Telegram audiences.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return void
     */
    protected function displayAudiences(BroadcastManager $manager): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green;options=bold>Telegram audiences</>');

        $bot = $this->laragram['config']->get('broadcasting.connections.'.$manager->getTelegramConnection().'.bot')
            ?? $this->laragram['config']->get('bot.default');

        foreach (['users', 'groups', 'supergroups', 'channels', 'chats'] as $name) {
            $detail = '<fg=gray>built-in</>';

            if ($this->option('count')) {
                try {
                    $detail = number_format($manager->store()->count((string) $bot, Audience::BUILT_IN[$name])).' reachable';
                } catch (Throwable $e) {
                    $detail = '<fg=yellow>'.$e->getMessage().'</>';
                }
            }

            $this->components->twoColumnDetail($name, $detail);
        }

        foreach ($manager->audiences()->all() as $name => $resolver) {
            $this->components->twoColumnDetail('<fg=blue>'.$name.'</>', $this->describe($resolver));
        }
    }

    /**
     * Display the private WebSocket channels.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return void
     */
    protected function displayChannels(BroadcastManager $manager): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green;options=bold>WebSocket channels</>');

        try {
            $channels = $manager->connection()->getChannels();
        } catch (Throwable) {
            $channels = collect();
        }

        if ($channels->isEmpty()) {
            $this->components->twoColumnDetail('<fg=gray>none registered on the default connection</>');
        }

        foreach ($channels as $name => $resolver) {
            $this->components->twoColumnDetail($name, $this->describe($resolver));
        }

        $this->newLine();
    }

    /**
     * Describe a resolver.
     *
     * @param  mixed  $resolver
     * @return string
     */
    protected function describe(mixed $resolver): string
    {
        return match (true) {
            $resolver instanceof Closure => 'Closure',
            is_string($resolver) => $resolver,
            is_array($resolver) => (is_object($resolver[0]) ? get_class($resolver[0]) : $resolver[0]).'@'.$resolver[1],
            is_object($resolver) => get_class($resolver),
            default => 'callable',
        };
    }
}
