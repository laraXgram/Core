<?php

namespace LaraGram\Foundation\Console\Dev;

use LaraGram\Console\Prompts\Key;
use LaraGram\Console\Prompts\Tui\Ansi;
use LaraGram\Console\Prompts\Tui\KeyHints;
use LaraGram\Console\Prompts\Tui\LogView;
use LaraGram\Console\Prompts\Tui\Screen;
use LaraGram\Console\Prompts\Tui\TabBar;
use Throwable;

class TuiRunner
{
    /**
     * The rows taken by the bars above and below the output.
     */
    protected const CHROME_ROWS = 2;

    /**
     * The output of each command.
     *
     * @var array<int, \LaraGram\Console\Prompts\Tui\LogView>
     */
    protected array $logs = [];

    /**
     * The interleaved output of every command.
     */
    protected LogView $stream;

    /**
     * The commands left out of the stream view.
     *
     * @var array<int, bool>
     */
    protected array $hidden = [];

    /**
     * The exit state of the commands that failed for good.
     *
     * @var array<int, bool>
     */
    protected array $failed = [];

    /**
     * The command being shown.
     */
    protected int $active = 0;

    /**
     * What the keys currently do: browse, search or filter.
     */
    protected string $mode = 'browse';

    /**
     * The search being typed.
     */
    protected string $query = '';

    /**
     * Whether the screen has to be drawn again.
     */
    protected bool $dirty = true;

    /**
     * Whether the run should end.
     */
    protected bool $stopping = false;

    /**
     * The width of the longest command name.
     */
    protected int $labelWidth;

    protected Supervisor $supervisor;

    /**
     * Create a new TUI runner instance.
     *
     * @param  list<array{name: string, command: string, color?: string}>  $commands
     */
    public function __construct(
        protected array $commands,
        protected string $cwd,
        protected bool $autoRestart,
        protected bool $timestamps,
        protected bool $streamMode,
        protected ?string $title,
        protected int $bufferSize,
        protected int $streamBufferSize,
        protected Screen $screen = new Screen,
    ) {
        $this->stream = new LogView($streamBufferSize);
        $this->labelWidth = max(array_map(fn ($command) => mb_strwidth($command['name']), $commands));

        foreach (array_keys($commands) as $index) {
            $this->logs[$index] = new LogView($bufferSize);
        }
    }

    /**
     * Run the commands until the user quits.
     */
    public function run(): int
    {
        $this->screen->enter($this->title);

        try {
            [$columns] = $this->screen->size();

            $this->supervisor = new Supervisor($this->commands, $this->cwd, $this->autoRestart, $this->handle(...), [
                'FORCE_COLOR' => '1',
                'COLUMNS' => (string) max(40, $columns - $this->labelWidth - 3),
            ]);

            $this->supervisor->start();

            $lastDraw = 0.0;

            while (! $this->stopping) {
                $this->supervisor->tick();

                foreach ($this->screen->keys() as $key) {
                    $this->press($key);
                }

                if ($this->dirty && microtime(true) - $lastDraw >= 0.033) {
                    $this->draw();
                    $this->dirty = false;
                    $lastDraw = microtime(true);
                }

                usleep(10_000);
            }

            $this->drawStopping();

            $this->supervisor->terminate();
        } catch (Throwable $e) {
            isset($this->supervisor) && $this->supervisor->terminate();

            throw $e;
        } finally {
            $this->screen->leave();
        }

        // The logs land in the real scrollback, so nothing is lost on the way out.
        foreach ($this->stream->lines() as $line) {
            @fwrite(STDOUT, $line."\n");
        }

        return 0;
    }

    /**
     * End the run, stopping the commands that are still running.
     */
    public function stop(): void
    {
        $this->stopping = true;
    }

    /**
     * Keep the output of an event of a command.
     */
    protected function handle(array $event): void
    {
        $index = $event['index'];

        if ($event['type'] === 'start') {
            unset($this->failed[$index]);
        }

        if ($event['type'] === 'failed') {
            $this->failed[$index] = true;
        }

        $text = match ($event['type']) {
            'output' => $event['text'],
            'error' => $this->notice('Failed to start: '.$event['message']),
            'exit' => $this->notice($event['signal'] !== null
                ? 'Process killed by signal '.$event['signal']
                : 'Process exited with code '.$event['code']),
            'restarting' => $this->notice("Restarting ({$event['attempt']}/{$event['max']})..."),
            default => null,
        };

        $this->dirty = true;

        if ($text === null) {
            return;
        }

        $time = $this->timestamps ? Ansi::dim(date('H:i:s').' ') : '';

        $this->logs[$index]->push($time.$text);
        $this->stream->push($time.$this->streamLabel($index).$text, $index);
    }

    /**
     * Act on a pressed key.
     */
    protected function press(string $key): void
    {
        $this->dirty = true;

        if ($key === Key::CTRL_C) {
            $this->stopping = true;

            return;
        }

        match ($this->mode) {
            'search' => $this->pressInSearch($key),
            'filter' => $this->pressInFilter($key),
            default => $this->pressInBrowse($key),
        };
    }

    /**
     * Act on a key while a search is being typed.
     */
    protected function pressInSearch(string $key): void
    {
        $log = $this->log();

        if ($key === Key::ESCAPE) {
            $this->mode = 'browse';
            $this->query = '';
            $log->search('');
            $log->scrollToBottom();

            return;
        }

        if ($key === Key::ENTER) {
            $this->mode = 'browse';
            $log->scrollToMatch($this->outputHeight());

            return;
        }

        if ($key === Key::BACKSPACE || $key === "\x08") {
            $this->query = mb_substr($this->query, 0, -1);
        } elseif (mb_strlen($key) === 1 && mb_ord($key) >= 32) {
            $this->query .= $key;
        } else {
            return;
        }

        $log->search($this->query);
        $log->scrollToMatch($this->outputHeight());
    }

    /**
     * Act on a key while commands are being chosen for the stream view.
     */
    protected function pressInFilter(string $key): void
    {
        if ($key === Key::ESCAPE || $key === 'f') {
            $this->mode = 'browse';

            return;
        }

        if (ctype_digit($key) && $key !== '0' && isset($this->commands[$index = (int) $key - 1])) {
            if (isset($this->hidden[$index])) {
                unset($this->hidden[$index]);
            } elseif (count($this->hidden) < count($this->commands) - 1) {
                $this->hidden[$index] = true;
            }

            $this->stream->filter($this->hidden === [] ? null : fn ($tag) => ! isset($this->hidden[$tag]));
        }
    }

    /**
     * Act on a key while browsing the output.
     */
    protected function pressInBrowse(string $key): void
    {
        $log = $this->log();
        $height = $this->outputHeight();

        if ($log->searchText() !== '') {
            match ($key) {
                'n' => $log->moveMatch(1, $height),
                'N' => $log->moveMatch(-1, $height),
                default => null,
            };

            if ($key === Key::ESCAPE) {
                $this->query = '';
                $log->search('');
                $log->scrollToBottom();
            }
        }

        match (true) {
            $key === 'q' => $this->stopping = true,
            $key === '/' => $this->startSearch(),
            $key === 'c' => $log->clear(),
            $key === 's' && ! $this->streamMode => $this->streamMode = true,
            $key === 't' && $this->streamMode => $this->streamMode = false,
            $key === 'f' && $this->streamMode => $this->mode = 'filter',
            $key === 'r' && ! $this->streamMode => $this->supervisor->restart($this->active),
            $key === Key::TAB || $key === Key::RIGHT || $key === Key::RIGHT_ARROW => $this->select($this->active + 1),
            $key === Key::SHIFT_TAB || $key === Key::LEFT || $key === Key::LEFT_ARROW => $this->select($this->active - 1),
            $key === Key::UP || $key === Key::UP_ARROW || $key === 'k' => $log->scroll(1, $height),
            $key === Key::DOWN || $key === Key::DOWN_ARROW || $key === 'j' => $log->scroll(-1, $height),
            $key === Key::PAGE_UP => $log->scroll($height, $height),
            $key === Key::PAGE_DOWN => $log->scroll(-$height, $height),
            $key === 'g' => $log->scrollToTop($height),
            $key === 'G' => $log->scrollToBottom(),
            ctype_digit($key) && $key !== '0' => isset($this->commands[(int) $key - 1]) && $this->select((int) $key - 1),
            default => null,
        };
    }

    /**
     * Start typing a search.
     */
    protected function startSearch(): void
    {
        $this->mode = 'search';
        $this->query = '';

        $this->log()->search('');
    }

    /**
     * Show the given command, going around at both ends.
     */
    protected function select(int $index): void
    {
        $count = count($this->commands);

        $this->active = ($index % $count + $count) % $count;
        $this->streamMode = false;
    }

    /**
     * Draw the whole screen.
     */
    protected function draw(): void
    {
        [$columns, $rows] = $this->screen->size();

        $log = $this->log();

        $log->resize($columns);

        $height = $this->outputHeight();

        $output = $log->render($height);

        $this->screen->draw([
            $this->header($columns),
            ...array_pad($output, $height, ''),
            $this->footer($columns),
        ]);
    }

    /**
     * Tell the user the commands are being stopped.
     */
    protected function drawStopping(): void
    {
        [$columns, $rows] = $this->screen->size();

        $this->screen->draw([
            ...array_fill(0, max(0, $rows - 1), ''),
            Ansi::dim(Ansi::truncate('Stopping...', $columns)),
        ]);
    }

    /**
     * Get the bar above the output.
     */
    protected function header(int $columns): string
    {
        $tabs = [];

        foreach ($this->commands as $index => $command) {
            $tabs[] = [
                'label' => $command['name'],
                'color' => $this->failed[$index] ?? false ? '#ef4444' : ($command['color'] ?? null),
                'marker' => $this->marker($index),
            ];
        }

        return TabBar::render($tabs, $this->streamMode ? -1 : $this->active, $columns);
    }

    /**
     * Get the mark that shows the state of a command.
     */
    protected function marker(int $index): string
    {
        return match (true) {
            $this->failed[$index] ?? false => '✖ ',
            $this->supervisor->status($index) === 'restarting' => '↻ ',
            $this->supervisor->status($index) === 'stopped' => '○ ',
            default => '● ',
        };
    }

    /**
     * Get the bar below the output.
     */
    protected function footer(int $columns): string
    {
        $log = $this->log();

        if ($this->mode === 'search') {
            return Ansi::truncate(Ansi::bold('/ ').$this->query.Ansi::dim('▏  enter accept  esc cancel'), $columns);
        }

        if ($this->mode === 'filter') {
            $names = [];

            foreach ($this->commands as $index => $command) {
                $names[] = ($index + 1).':'.(isset($this->hidden[$index]) ? Ansi::dim($command['name']) : Ansi::color($command['name'], $command['color'] ?? null));
            }

            return KeyHints::render(['1-9' => 'toggle', 'f' => 'done'], $columns, implode(' ', $names));
        }

        if ($log->searchText() !== '') {
            $found = $log->matchCount() === 0 ? 'no matches' : ($log->currentMatch() + 1).'/'.$log->matchCount();

            return KeyHints::render(['n' => 'next', 'N' => 'prev', 'esc' => 'clear'], $columns, $found.' '.Ansi::bold($log->searchText()));
        }

        $bindings = $this->streamMode
            ? ['↑/↓' => 'scroll', 'f' => 'filter', 'c' => 'clear', '/' => 'search', 't' => 'tabs', 'q' => 'quit']
            : ['←/→' => 'tabs', '↑/↓' => 'scroll', 'r' => 'restart', 'c' => 'clear', '/' => 'search', 's' => 'stream', 'q' => 'quit'];

        return KeyHints::render($bindings, $columns, $log->isFollowing() ? '' : Ansi::dim('scrolled'));
    }

    /**
     * Get the log being shown.
     */
    protected function log(): LogView
    {
        return $this->streamMode ? $this->stream : $this->logs[$this->active];
    }

    /**
     * Get the rows available for the output.
     */
    protected function outputHeight(): int
    {
        return max(1, $this->screen->size()[1] - self::CHROME_ROWS);
    }

    /**
     * Get the label of a command in the stream view.
     */
    protected function streamLabel(int $index): string
    {
        $name = $this->commands[$index]['name'];

        return Ansi::color(str_pad($name, $this->labelWidth + strlen($name) - mb_strwidth($name)).' │', $this->commands[$index]['color'] ?? null).' ';
    }

    /**
     * Format a notice about a command.
     */
    protected function notice(string $text): string
    {
        return "\e[2m\e[3m{$text}".Ansi::RESET;
    }
}
