<?php

namespace LaraGram\Console\Prompts\Tui;

use Closure;

class LogView
{
    /**
     * The lines of the log, with what they were tagged with.
     *
     * @var list<array{0: string, 1: mixed}>
     */
    protected array $lines = [];

    /**
     * The visible lines broken into rows at the current width.
     *
     * @var list<string>
     */
    protected array $rows = [];

    /**
     * The width the rows were broken at.
     */
    protected int $width = 80;

    /**
     * The rows between the last row and the last row shown; 0 follows the end.
     */
    protected int $offset = 0;

    /**
     * The tags of the lines to show, or null to show every line.
     */
    protected ?Closure $filter = null;

    /**
     * The text being searched for.
     */
    protected string $search = '';

    /**
     * The rows the search matches.
     *
     * @var list<int>
     */
    protected array $matches = [];

    /**
     * The position of the selected match.
     */
    protected int $match = 0;

    /**
     * Create a new log view instance.
     */
    public function __construct(
        protected int $limit = 2000,
    ) {
    }

    /**
     * Add a line to the end of the log.
     */
    public function push(string $line, mixed $tag = null): void
    {
        $this->lines[] = [$line, $tag];

        if (count($this->lines) > (int) ($this->limit * 1.1) + 1) {
            $this->lines = array_slice($this->lines, -$this->limit);
            $this->rebuild();

            return;
        }

        if ($this->filter !== null && ! ($this->filter)($tag)) {
            return;
        }

        $rows = Ansi::wrap($line, $this->width);

        array_push($this->rows, ...$rows);

        // Someone reading further up keeps their place while the log grows.
        if ($this->offset > 0) {
            $this->offset += count($rows);
        }

        if ($this->search !== '') {
            $this->findMatches();
        }
    }

    /**
     * Remove every line.
     */
    public function clear(): void
    {
        $this->lines = [];
        $this->rows = [];
        $this->matches = [];
        $this->offset = 0;
        $this->match = 0;
    }

    /**
     * Get the lines of the log, oldest first.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return array_column($this->lines, 0);
    }

    /**
     * Only show the lines whose tag passes the given callback.
     */
    public function filter(?Closure $filter): void
    {
        $this->filter = $filter;
        $this->rebuild();
    }

    /**
     * Break the log into rows at the given width.
     */
    public function resize(int $width): void
    {
        if ($width !== $this->width) {
            $this->width = max(1, $width);
            $this->rebuild();
        }
    }

    /**
     * Scroll by the given number of rows; a positive number scrolls up.
     */
    public function scroll(int $rows, int $height): void
    {
        $this->offset = max(0, min($this->offset + $rows, max(0, count($this->rows) - $height)));
    }

    /**
     * Scroll to the first row.
     */
    public function scrollToTop(int $height): void
    {
        $this->offset = max(0, count($this->rows) - $height);
    }

    /**
     * Scroll to the last row and follow the log again.
     */
    public function scrollToBottom(): void
    {
        $this->offset = 0;
    }

    /**
     * Search for the given text, selecting the last match.
     */
    public function search(string $text): void
    {
        $this->search = $text;

        $this->findMatches();

        $this->match = max(0, count($this->matches) - 1);
    }

    /**
     * Get the text being searched for.
     */
    public function searchText(): string
    {
        return $this->search;
    }

    /**
     * Get the number of rows the search matches.
     */
    public function matchCount(): int
    {
        return count($this->matches);
    }

    /**
     * Get the position of the selected match, counted from the first one.
     */
    public function currentMatch(): int
    {
        return $this->match;
    }

    /**
     * Select another match, showing it in the middle of the view.
     */
    public function moveMatch(int $by, int $height): void
    {
        if (($count = count($this->matches)) === 0) {
            return;
        }

        $this->match = (($this->match + $by) % $count + $count) % $count;

        $this->scrollToMatch($height);
    }

    /**
     * Show the selected match in the middle of the view.
     */
    public function scrollToMatch(int $height): void
    {
        if ($this->matches === []) {
            return;
        }

        $fromEnd = count($this->rows) - 1 - $this->matches[$this->match];

        $this->offset = max(0, min($fromEnd - (int) ($height / 2), max(0, count($this->rows) - $height)));
    }

    /**
     * Get the rows to show in a view of the given height.
     *
     * @return list<string>
     */
    public function render(int $height): array
    {
        $total = count($this->rows);
        $end = max(0, $total - $this->offset);
        $start = max(0, $end - $height);

        $rows = array_slice($this->rows, $start, $end - $start);

        if ($this->search !== '' && $this->matches !== []) {
            foreach ($rows as $index => $row) {
                if (in_array($start + $index, $this->matches, true)) {
                    $rows[$index] = $this->highlight($row, $this->matches[$this->match] === $start + $index);
                }
            }
        }

        return $rows;
    }

    /**
     * Determine if the view is following the end of the log.
     */
    public function isFollowing(): bool
    {
        return $this->offset === 0;
    }

    /**
     * Break the visible lines into rows again.
     */
    protected function rebuild(): void
    {
        $rows = [];

        foreach ($this->lines as [$line, $tag]) {
            if ($this->filter === null || ($this->filter)($tag)) {
                foreach (Ansi::wrap($line, $this->width) as $row) {
                    $rows[] = $row;
                }
            }
        }

        $this->rows = $rows;
        $this->offset = min($this->offset, max(0, count($rows) - 1));

        $this->findMatches();
    }

    /**
     * Find the rows the search matches.
     */
    protected function findMatches(): void
    {
        $this->matches = [];

        if ($this->search === '') {
            return;
        }

        foreach ($this->rows as $index => $row) {
            if (mb_stripos(Ansi::strip($row), $this->search) !== false) {
                $this->matches[] = $index;
            }
        }

        $this->match = min($this->match, max(0, count($this->matches) - 1));
    }

    /**
     * Mark the matches in the given row.
     */
    protected function highlight(string $row, bool $selected): string
    {
        $plain = Ansi::strip($row);

        return preg_replace_callback(
            '/'.preg_quote($this->search, '/').'/iu',
            fn ($match) => ($selected ? "\e[30;43m" : "\e[7m").$match[0].Ansi::RESET,
            $plain,
        ) ?? $plain;
    }
}
