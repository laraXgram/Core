<?php

namespace LaraGram\Console\Prompts\Tui;

use LaraGram\Console\Prompts\Terminal;
use Throwable;

class Screen
{
    /**
     * The rows drawn last, so that only the rows that changed are drawn again.
     *
     * @var array<int, string>
     */
    protected array $frame = [];

    /**
     * Whether the screen is currently taken over.
     */
    protected bool $entered = false;

    /**
     * Whether the terminal title was changed.
     */
    protected bool $titled = false;

    /**
     * The keys read but not parsed yet.
     */
    protected string $pendingKeys = '';

    /**
     * The time the size of the terminal was last read.
     */
    protected float $sizeReadAt = 0;

    /**
     * Create a new screen instance.
     */
    public function __construct(
        protected Terminal $terminal = new Terminal,
    ) {
    }

    /**
     * Determine if a screen can be taken over in this terminal.
     */
    public static function isSupported(): bool
    {
        return PHP_OS_FAMILY !== 'Windows'
            && stream_isatty(STDIN)
            && stream_isatty(STDOUT)
            && getenv('TERM') !== 'dumb';
    }

    /**
     * Take over the terminal.
     */
    public function enter(?string $title = null): void
    {
        $this->terminal->setTty('-icanon -isig -echo');

        stream_set_blocking(STDIN, false);

        $this->write("\e[?1049h\e[?25l\e[2J");

        if ($title !== null) {
            $this->titled = true;
            $this->write("\e[22;0t\e]0;".preg_replace('/[\x00-\x1f\x7f-\x9f]/', '', $title)."\x07");
        }

        $this->entered = true;
        $this->frame = [];
    }

    /**
     * Give the terminal back as it was.
     */
    public function leave(): void
    {
        if (! $this->entered) {
            return;
        }

        $this->entered = false;

        $this->write(($this->titled ? "\e[23;0t" : '')."\e[?25h\e[?1049l");

        stream_set_blocking(STDIN, true);

        try {
            $this->terminal->restoreTty();
        } catch (Throwable) {
            // The terminal may be gone already.
        }
    }

    /**
     * Draw the given rows, leaving the ones that did not change alone.
     *
     * @param  list<string>  $rows
     */
    public function draw(array $rows): void
    {
        [$columns, $height] = $this->size();

        $output = '';

        for ($row = 0; $row < $height; $row++) {
            $line = Ansi::truncate($rows[$row] ?? '', $columns);

            if (($this->frame[$row] ?? null) !== $line) {
                $output .= sprintf("\e[%d;1H%s\e[0m\e[K", $row + 1, $line);
                $this->frame[$row] = $line;
            }
        }

        if ($output !== '') {
            $this->write("\e[?2026h".$output."\e[?2026l");
        }
    }

    /**
     * Get the width and the height of the terminal.
     *
     * @return array{0: int, 1: int}
     */
    public function size(): array
    {
        if (microtime(true) - $this->sizeReadAt >= 1) {
            $this->sizeReadAt = microtime(true);

            try {
                $this->terminal->initDimensions();
            } catch (Throwable) {
                // Keep the last known size.
            }
        }

        return [max(1, $this->terminal->cols()), max(1, $this->terminal->lines())];
    }

    /**
     * Forget what was drawn, so that the next frame is drawn in full.
     */
    public function refresh(): void
    {
        $this->frame = [];

        $this->write("\e[2J");
    }

    /**
     * Read the keys pressed since the last call, without waiting for any.
     *
     * Each key is one entry, comparable with the constants of the Key class.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        $input = @fread(STDIN, 1024);

        if ($input === false || $input === '') {
            return [];
        }

        preg_match_all('/\e\[[0-9;?]*[ -\/]*[@-~]|\eO.|\e.|./su', $input, $matches);

        return array_map(fn ($key) => $key === "\r" ? "\n" : $key, $matches[0]);
    }

    /**
     * Write to the terminal.
     */
    protected function write(string $output): void
    {
        @fwrite(STDOUT, $output);
    }
}
