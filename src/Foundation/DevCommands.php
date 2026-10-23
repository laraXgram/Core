<?php

namespace LaraGram\Foundation;

use LaraGram\Support\Arr;
use LaraGram\Support\Facades\File;
use LaraGram\Support\NodePackageManager;
use ReflectionClass;

/**
 * @phpstan-type DevCommandArray array{'name': string, 'command': string, 'source': array{'file': string, 'line': int, 'class'?: string, 'function'?: string}, 'color': string}
 */
class DevCommands
{
    /**
     * The resolved NodePackageManager instance.
     *
     * @var NodePackageManager|null
     */
    protected static ?NodePackageManager $packageManager = null;

    /**
     * Counter to keep track of how many colors have been assigned.
     *
     * Used to ensure colors are reused only after all have been used at least once.
     *
     * @var int
     */
    protected static $colorCount = 0;

    /**
     * The registered development commands.
     *
     * @var array
     */
    protected static $commands = [];

    /**
     * The names of commands that should be included when running the "dev" command.
     *
     * @var array<int, string>
     */
    protected static $only = [];

    /**
     * The names of commands that should be excluded when running the "dev" command.
     *
     * @var array<int, string>
     */
    protected static $except = [];

    /**
     * The order in which named commands should run when running the "dev" command.
     *
     * @var array<int, string>
     */
    protected static $order = [];

    /**
     * The mode in which the "dev" command should run.
     *
     * @var DevCommandMode
     */
    protected static DevCommandMode $mode = DevCommandMode::TABS;

    /**
     * Whether to include timestamps in the output of the "dev" command.
     *
     * @var bool
     */
    protected static $withTimestamps = false;

    /**
     * Whether to automatically restart a "dev" command when it fails.
     *
     * @var bool
     */
    protected static $autoRestart = true;

    /**
     * Whether to exclude vendor commands.
     *
     * @var bool
     */
    protected static $withoutVendorCommands = false;

    /**
     * Whether to exclude the framework's default commands.
     *
     * @var bool
     */
    protected static $withoutDefaultCommands = false;

    /**
     * How many lines of output to buffer for each command when running in tabbed mode.
     *
     * @var int|null
     */
    protected static ?int $bufferSize = null;

    /**
     * How many lines of output to buffer total when running in stream mode.
     *
     * @var int|null
     */
    protected static ?int $streamBufferSize = null;

    /**
     * Register the default development commands.
     *
     * @return void
     */
    public static function registerDefaults()
    {
        if (! app()->runningInConsole()) {
            return;
        }

        // Surge registers itself as the "server" command when it is installed...
        self::commander('serve --host=localhost', 'server');

        if (self::usesLocalApiServer()) {
            self::commander('start:apiserver', 'apiserver');
        }

        // The MTProto client runs as a Surge process, unless its pump is disabled...
        if (app()->providerIsLoaded('LaraGram\\MTProto\\Foundation\\ClientServiceProvider') && ! config('mtproto.use_pump', true)) {
            self::commander('client:start', 'client');
        }

        self::commander('queue:listen --tries=1 --timeout=0', 'queue');

        if (app()->providerIsLoaded('LaraGram\\Watchdog\\WatchdogServiceProvider')) {
            self::commander('watchdog --timeout=0', 'logs');
        }

        if (File::exists(base_path('package.json'))) {
            self::node('dev', 'vite');
        }
    }

    /**
     * Determine if the bot talks to a local Bot API server started by the application.
     *
     * @return bool
     */
    protected static function usesLocalApiServer(): bool
    {
        $host = parse_url((string) config('bot.api_server.endpoint'), PHP_URL_HOST);

        return in_array($host, ['127.0.0.1', 'localhost', '::1', '[::1]'], true)
            && filled(config('bot.api_server.api_id'))
            && filled(config('bot.api_server.api_hash'));
    }

    /**
     * Register a development command.
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function register(string $command, ?string $name = null): DevCommand
    {
        if (! app()->runningInConsole()) {
            return new DevCommand('', [], '');
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $source = self::resolveSource($trace);
        $priority = self::resolvePriority($trace);

        $devCommand = new DevCommand($command, $source, $name, $priority);

        $existing = self::$commands[$devCommand->name()] ?? null;

        if (! $existing || $devCommand->priority() >= $existing->priority()) {
            self::$commands[$devCommand->name()] = $devCommand;
        }

        return $devCommand;
    }

    /**
     * Registers a Commander command, automatically prefixing it with "php laragram".
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function commander(string $command, ?string $name = null): DevCommand
    {
        return self::register("php laragram {$command}", $name ?? DevCommand::nameFromCommand($command));
    }

    /**
     * Registers a Node command, automatically prefixing it with the detected package manager's run command.
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function node(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getRunCommand($command), $name ?? DevCommand::nameFromCommand($command));
    }

    /**
     * Registers a Node command, automatically prefixing it with the detected package manager's exec command.
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function nodeExec(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getExecCommand($command), $name ?? DevCommand::nameFromCommand($command));
    }

    /**
     * Get the registered development commands.
     *
     * @return DevCommandArray[]
     */
    public static function commands(): array
    {
        $commands = [];

        foreach (self::$commands as $command) {
            if (self::$withoutVendorCommands && $command->priority() === DevCommand::PRIORITY_VENDOR) {
                continue;
            }

            if (self::$withoutDefaultCommands && $command->priority() === DevCommand::PRIORITY_DEFAULT) {
                continue;
            }

            $cmd = $command->toArray();

            if ((! empty(self::$only) && ! in_array($cmd['name'], self::$only)) || in_array($cmd['name'], self::$except)) {
                continue;
            }

            $commands[] = $cmd;
        }

        $commands = self::applyOrder($commands);

        return self::fillInEmptyColors($commands);
    }

    /**
     * Set the mode to inline, where all commands are run in the same terminal window.
     *
     * No-op on Windows.
     *
     * @return void
     */
    public static function inline(): void
    {
        self::$mode = DevCommandMode::INLINE;
    }

    /**
     * Set the mode to stream, where all commands are run in the same terminal window, but their output is interactive within a TUI.
     *
     * No-op on Windows.
     *
     * @return void
     */
    public static function stream(): void
    {
        self::$mode = DevCommandMode::STREAM;
    }

    /**
     * Set the mode to tabs, where each command is run in its own terminal tab.
     *
     * No-op on Windows.
     *
     * @return void
     */
    public static function tabs(): void
    {
        self::$mode = DevCommandMode::TABS;
    }

    /**
     * Get the mode in which the "dev" command should run.
     *
     * @return DevCommandMode
     */
    public static function mode(): DevCommandMode
    {
        return self::$mode;
    }

    /**
     * Enable timestamps in the output of the "dev" command.
     *
     * @return void
     */
    public static function withTimestamps(): void
    {
        self::$withTimestamps = true;
    }

    /**
     * Determine if timestamps should be included in the output of the "dev" command.
     *
     * @return bool
     */
    public static function shouldIncludeTimestamps(): bool
    {
        return self::$withTimestamps;
    }

    /**
     * Disable automatic restart of a "dev" command when it fails.
     *
     * @return void
     */
    public static function disableAutoRestart(): void
    {
        self::$autoRestart = false;
    }

    /**
     * Determine if a "dev" command should automatically restart when it fails.
     *
     * @return bool
     */
    public static function shouldAutoRestart(): bool
    {
        return self::$autoRestart;
    }

    /**
     * Set the number of lines of output to buffer for each command when running in tabbed mode.
     *
     * No-op on Windows.
     *
     * @param  int  $lines
     * @return void
     */
    public static function bufferSize(int $lines): void
    {
        self::$bufferSize = $lines;
    }

    /**
     * Get the number of lines of output to buffer for each command when running in tabbed mode.
     *
     * @return int|null
     */
    public static function getBufferSize(): ?int
    {
        return self::$bufferSize;
    }

    /**
     * Set the number of lines of output to buffer total when running in stream mode.
     *
     * No-op on Windows.
     *
     * @param  int  $lines
     * @return void
     */
    public static function streamBufferSize(int $lines): void
    {
        self::$streamBufferSize = $lines;
    }

    /**
     * Get the number of lines of output to buffer total when running in stream mode.
     *
     * @return int|null
     */
    public static function getStreamBufferSize(): ?int
    {
        return self::$streamBufferSize;
    }

    /**
     * Sort the given commands.
     *
     * @param  array  $commands
     * @return array
     */
    protected static function applyOrder(array $commands): array
    {
        if (empty(self::$order)) {
            return $commands;
        }

        $positions = array_flip(self::$order);

        $commands = Arr::sort($commands, fn ($command) => $positions[$command['name']] ?? PHP_INT_MAX);

        return array_values($commands);
    }

    /**
     * Fill in any empty colors in the given commands array, ensuring each command has a color assigned.
     *
     * @param  array  $commands
     * @return array
     */
    protected static function fillInEmptyColors(array $commands): array
    {
        foreach ($commands as &$command) {
            if (empty($command['color'])) {
                $command['color'] = self::getColor($commands);
            }
        }

        return $commands;
    }

    /**
     * Get a color for a command, ensuring that colors are reused only after all available colors have been used at least once.
     *
     * @param  array  $commands
     * @return string
     */
    protected static function getColor(array $commands): string
    {
        $available = array_values(array_diff(
            $colors = array_map(fn ($color) => $color->value, DevCommandColor::cases()),
            $existing = array_values(array_filter(array_column($commands, 'color')))
        ));

        return $available[0] ?? $colors[self::$colorCount++ % count($colors)];
    }

    /**
     * Resolve the first external caller frame from a debug backtrace.
     *
     * @param  array<int, array{'file': string, 'line': int, 'class'?: string, 'function'?: string}>  $trace
     * @return array{'file': string, 'line': int, 'class'?: string, 'function'?: string}
     */
    protected static function resolveSource(array $trace): array
    {
        foreach ($trace as $frame) {
            if (($frame['file'] ?? null) === __FILE__) {
                continue;
            }

            if (($frame['class'] ?? null) === self::class) {
                continue;
            }

            return $frame;
        }

        return [];
    }

    /**
     * Determine the registration priority from a debug backtrace.
     *
     * @param  array<int, array{'file': string, 'line': int, 'class'?: string, 'function'?: string}>  $trace
     * @return int
     */
    protected static function resolvePriority(array $trace): int
    {
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? null;
            $class = $frame['class'] ?? null;

            if ($file === __FILE__) {
                continue;
            }

            if ($class === self::class && ($frame['function'] ?? null) === 'registerDefaults') {
                return DevCommand::PRIORITY_DEFAULT;
            }

            if (! $file && $class) {
                $file = (new ReflectionClass($class))->getFileName();
            }

            if (! $file || $file === base_path('laragram')) {
                continue;
            }

            if (! str_contains($file, base_path('vendor'))) {
                return DevCommand::PRIORITY_USERLAND;
            }
        }

        return DevCommand::PRIORITY_VENDOR;
    }

    /**
     * Set the commands that should be included when running the "dev" command.
     *
     * @param  string  ...$names
     * @return void
     */
    public static function only(...$names): void
    {
        self::$only = $names;
    }

    /**
     * Set the commands that should be excluded when running the "dev" command.
     *
     * @param  string  ...$names
     * @return void
     */
    public static function except(...$names): void
    {
        self::$except = $names;
    }

    /**
     * Set the order in which named commands should run when running the "dev" command.
     *
     * @param  array<int, string>  $names
     * @return void
     */
    public static function order(array $names): void
    {
        self::$order = $names;
    }

    /**
     * Exclude any commands from the vendor directory.
     *
     * @return void
     */
    public static function withoutVendorCommands(): void
    {
        self::$withoutVendorCommands = true;
    }

    /**
     * Exclude the framework's default commands.
     *
     * @return void
     */
    public static function withoutDefaultCommands(): void
    {
        self::$withoutDefaultCommands = true;
    }

    /**
     * Resolve and return the NodePackageManager instance.
     *
     * @return NodePackageManager
     */
    protected static function getPackageManager(): NodePackageManager
    {
        return self::$packageManager ??= new NodePackageManager();
    }
}
