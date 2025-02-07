<?php

namespace LaraGram\Console\Prompts;

class Table extends Prompt
{
    /**
     * The table headers.
     *
     * @var array<int, string|array<int, string>>
     */
    public array $headers;

    /**
     * The table rows.
     *
     * @var array<int, array<int, string>>
     */
    public array $rows;

    /**
     * Create a new Table instance.
     *
     * @param  array<int, string|array<int, string>>  $headers
     * @param  array<int, array<int, string>>  $rows
     *
     * @phpstan-param ($rows is null ? list<list<string>> : list<string|list<string>>) $headers
     */
    public function __construct(array $headers = [], array|null $rows = null)
    {
        if ($rows === null) {
            $rows = $headers;
            $headers = [];
        }

        $this->headers = $headers;
        $this->rows = $rows;
    }

    /**
     * Display the table.
     */
    public function display(): void
    {
        $this->prompt();
    }

    /**
     * Display the table.
     */
    public function prompt(): bool
    {
        $this->capturePreviousNewLines();

        $this->state = 'submit';

        static::output()->write($this->renderTheme());

        return true;
    }

    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
}
