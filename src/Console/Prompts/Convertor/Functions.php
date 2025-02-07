<?php

declare(strict_types=1);

namespace LaraGram\Console\Prompts\Convertor;

use Closure;
use LAraGram\Console\Output\OutputInterface;
use LaraGram\Console\Prompts\Convertor\Repositories\Styles as StyleRepository;
use LaraGram\Console\Prompts\Convertor\ValueObjects\Style;
use LaraGram\Console\Prompts\Convertor\ValueObjects\Styles;

if (! function_exists('LaraGram\Console\Prompts\Convertor\renderUsing')) {
    /**
     * Sets the renderer implementation.
     */
    function renderUsing(?OutputInterface $renderer): void
    {
        Convertor::renderUsing($renderer);
    }
}

if (! function_exists('LaraGram\Console\Prompts\Convertor\style')) {
    /**
     * Creates a new style.
     *
     * @param  (Closure(Styles $renderable, string|int ...$arguments): Styles)|null  $callback
     */
    function style(string $name, ?Closure $callback = null): Style
    {
        return StyleRepository::create($name, $callback);
    }
}

if (! function_exists('LaraGram\Console\Prompts\Convertor\render')) {
    /**
     * Render HTML to the terminal.
     */
    function render(string $html, int $options = OutputInterface::OUTPUT_NORMAL): void
    {
        (new HtmlRenderer)->render($html, $options);
    }
}

if (! function_exists('LaraGram\Console\Prompts\Convertor\parse')) {
    /**
     * Parse HTML to a string that can be rendered in the terminal.
     */
    function parse(string $html): string
    {
        return (new HtmlRenderer)->parse($html)->toString();
    }
}

if (! function_exists('LaraGram\Console\Prompts\Convertor\terminal')) {
    /**
     * Returns a Terminal instance.
     */
    function terminal(): Terminal
    {
        return new Terminal;
    }
}

if (! function_exists('LaraGram\Console\Prompts\Convertor\ask')) {
    /**
     * Renders a prompt to the user.
     *
     * @param  iterable<array-key, string>|null  $autocomplete
     */
    function ask(string $question, ?iterable $autocomplete = null): mixed
    {
        return (new Question)->ask($question, $autocomplete);
    }
}
