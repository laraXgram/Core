<?php

namespace LaraGram\Foundation\Console\Dev;

use DateTimeImmutable;
use LaraGram\Console\Prompts\Tui\Ansi;

class InlineRunner
{
    /**
     * The version of the JSON events.
     */
    protected const JSON_VERSION = 1;

    /**
     * The width of the longest command name.
     */
    protected int $labelWidth;

    /**
     * The exit code of the first command that failed for good.
     */
    protected ?int $failure = null;

    /**
     * Whether the run should end.
     */
    protected bool $stopping = false;

    /**
     * Create a new inline runner instance.
     *
     * @param  list<array{name: string, command: string, color?: string}>  $commands
     */
    public function __construct(
        protected array $commands,
        protected string $cwd,
        protected bool $autoRestart,
        protected bool $timestamps,
        protected bool $json,
        protected bool $color,
    ) {
        $this->labelWidth = max(array_map(fn ($command) => mb_strwidth($command['name']), $commands));
    }

    /**
     * Run the commands until the last one ends.
     */
    public function run(): int
    {
        $env = $this->color ? ['FORCE_COLOR' => '1'] : [];

        $supervisor = new Supervisor($this->commands, $this->cwd, $this->autoRestart, $this->handle(...), $env);

        $supervisor->start();

        while (! $this->stopping && ! $supervisor->isSettled()) {
            $supervisor->tick();

            usleep(25_000);
        }

        if ($this->stopping) {
            $supervisor->terminate();
        }

        $code = $this->failure ?? 0;

        if ($this->json) {
            $this->emit(['type' => 'done', 'code' => $code]);
        }

        return $code;
    }

    /**
     * End the run, stopping the commands that are still running.
     */
    public function stop(): void
    {
        $this->stopping = true;
    }

    /**
     * Report an event of a command.
     */
    protected function handle(array $event): void
    {
        $command = $this->commands[$event['index']];

        if ($event['type'] === 'failed' && $this->failure === null) {
            $this->failure = empty($event['code']) ? 1 : $event['code'];
        }

        if ($this->json) {
            $this->emit($this->jsonEvent($event, $command));

            return;
        }

        match ($event['type']) {
            'output' => $this->line($event['index'], $event['text'], $event['stream'] === 'stderr' ? STDERR : STDOUT),
            'error' => $this->system($event['index'], 'Failed to start: '.$event['message']),
            'exit' => $this->system($event['index'], $event['signal'] !== null
                ? 'Process killed by signal '.$event['signal']
                : 'Process exited with code '.$event['code']),
            'restarting' => $this->system($event['index'], "Restarting ({$event['attempt']}/{$event['max']})..."),
            default => null,
        };
    }

    /**
     * Get the JSON form of an event.
     */
    protected function jsonEvent(array $event, array $command): array
    {
        return match ($event['type']) {
            'start' => ['type' => 'start', 'label' => $command['name'], 'command' => $command['command'], 'pid' => $event['pid']],
            'output' => ['type' => 'output', 'label' => $command['name'], 'stream' => $event['stream'], 'text' => Ansi::strip($event['text'])],
            'exit' => ['type' => 'exit', 'label' => $command['name'], 'code' => $event['code'], 'signal' => $event['signal']],
            'restarting' => ['type' => 'restarting', 'label' => $command['name'], 'attempt' => $event['attempt'], 'max' => $event['max']],
            'error' => ['type' => 'error', 'label' => $command['name'], 'message' => $event['message']],
            'failed' => ['type' => 'failed', 'label' => $command['name'], 'code' => $event['code'], 'reason' => $event['reason']],
        };
    }

    /**
     * Write one line of a command.
     *
     * @param  resource  $stream
     */
    protected function line(int $index, string $text, $stream): void
    {
        $label = str_pad($this->commands[$index]['name'], $this->labelWidth + strlen($this->commands[$index]['name']) - mb_strwidth($this->commands[$index]['name']));

        $prefix = $this->color
            ? Ansi::color($label.' │', $this->commands[$index]['color'] ?? null).' '
            : $label.' │ ';

        $time = $this->timestamps ? ($this->color ? Ansi::dim(date('H:i:s').' ') : date('H:i:s').' ') : '';

        @fwrite($stream, $time.$prefix.($this->color ? $text : Ansi::strip($text))."\n");
    }

    /**
     * Write a notice about a command; it is not part of the command's output.
     */
    protected function system(int $index, string $text): void
    {
        $this->line($index, $this->color ? "\e[2m\e[3m{$text}".Ansi::RESET : $text, STDERR);
    }

    /**
     * Write one JSON event.
     */
    protected function emit(array $event): void
    {
        $time = (new DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');

        @fwrite(STDOUT, json_encode(['v' => self::JSON_VERSION, 'time' => $time, ...$event], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    }
}
