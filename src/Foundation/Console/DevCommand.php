<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Command;
use LaraGram\Console\Prohibitable;
use LaraGram\Console\Prompts\Tui\Screen;
use LaraGram\Foundation\Console\Dev\InlineRunner;
use LaraGram\Foundation\Console\Dev\TuiRunner;
use LaraGram\Foundation\DevCommandMode;
use LaraGram\Foundation\DevCommands;

/**
 * @phpstan-import-type DevCommandArray from \LaraGram\Foundation\DevCommands
 */
#[AsCommand(name: 'dev')]
class DevCommand extends Command
{
    use Prohibitable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev
        {--s|stream : Start in stream mode}
        {--t|tabs : Start in tabs mode}
        {--i|inline : Print output inline instead of rendering the TUI (the default when not a TTY)}
        {--timestamps : Display timestamps on each output line}
        {--no-restart : Disable auto-restart on crash}
        {--json : Emit newline-delimited JSON events. Implies --inline}
        {--buffer-size= : Set the max lines per command buffer}
        {--stream-buffer-size= : Set the max lines in the stream buffer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the dev processes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $commands = DevCommands::commands();

        if ($commands === []) {
            $this->components->warn('No dev commands are registered.');

            return self::SUCCESS;
        }

        $mode = $this->mode();

        $runner = $mode === DevCommandMode::INLINE || $this->option('json') || ! Screen::isSupported()
            ? $this->inlineRunner($commands)
            : $this->tuiRunner($commands, $mode);

        $stop = fn () => $runner->stop();

        $this->trap(fn () => [SIGINT, SIGTERM, SIGHUP, SIGQUIT], $stop);

        if (function_exists('sapi_windows_set_ctrl_handler')) {
            sapi_windows_set_ctrl_handler($stop);
        }

        try {
            return $runner->run();
        } finally {
            if (function_exists('sapi_windows_set_ctrl_handler')) {
                sapi_windows_set_ctrl_handler($stop, false);
            }
        }
    }

    /**
     * Determine the mode to run the commands in.
     *
     * @return \LaraGram\Foundation\DevCommandMode
     */
    protected function mode(): DevCommandMode
    {
        return match (true) {
            $this->option('tabs') => DevCommandMode::TABS,
            $this->option('stream') => DevCommandMode::STREAM,
            $this->option('inline') => DevCommandMode::INLINE,
            default => DevCommands::mode(),
        };
    }

    /**
     * Create the runner that prints the output as it arrives.
     *
     * @param  DevCommandArray[]  $commands
     * @return \LaraGram\Foundation\Console\Dev\InlineRunner
     */
    protected function inlineRunner(array $commands): InlineRunner
    {
        return new InlineRunner(
            $commands,
            base_path(),
            $this->shouldRestart(),
            $this->shouldIncludeTimestamps(),
            (bool) $this->option('json'),
            $this->output->isDecorated(),
        );
    }

    /**
     * Create the runner that takes over the terminal.
     *
     * @param  DevCommandArray[]  $commands
     * @param  \LaraGram\Foundation\DevCommandMode  $mode
     * @return \LaraGram\Foundation\Console\Dev\TuiRunner
     */
    protected function tuiRunner(array $commands, DevCommandMode $mode): TuiRunner
    {
        return new TuiRunner(
            $commands,
            base_path(),
            $this->shouldRestart(),
            $this->shouldIncludeTimestamps(),
            $mode === DevCommandMode::STREAM,
            'laragram dev · '.(config('app.name') === 'LaraGram' ? basename(base_path()) : config('app.name')),
            (int) ($this->option('buffer-size') ?? DevCommands::getBufferSize() ?? 2000),
            (int) ($this->option('stream-buffer-size') ?? DevCommands::getStreamBufferSize() ?? 10000),
        );
    }

    /**
     * Determine if a command that crashes should be started again.
     *
     * @return bool
     */
    protected function shouldRestart(): bool
    {
        return ! $this->option('no-restart') && DevCommands::shouldAutoRestart();
    }

    /**
     * Determine if the output lines should show the time.
     *
     * @return bool
     */
    protected function shouldIncludeTimestamps(): bool
    {
        return $this->option('timestamps') || DevCommands::shouldIncludeTimestamps();
    }
}
